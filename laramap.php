#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * LaraMap — mapa de fluxo para projetos Laravel 12 (Sail + Livewire + Spatie).
 * Arquivo unico, sem dependencias. Uso: php laramap.php /caminho/do/projeto
 */

// O padrao de muitas instalacoes (principalmente CLI no Windows) e 128M, o
// que estoura ao tokenizar um unico arquivo grande (seeders com arrays
// grandes, por exemplo). Isso processa um projeto inteiro de uma vez só, em
// processo isolado de curta duracao — não é uma requisição web — então
// remover o teto aqui é seguro.
ini_set('memory_limit', '-1');

/**
 * Catálogo dos arquivos que nascem junto com um projeto Laravel 12
 * (+ Sail, Livewire, Spatie Permission). Tudo que estiver aqui é
 * considerado "padrão de instalação" e fica fora do mapa.
 */
final class Stock
{
    /** Diretórios que nunca são lidos. */
    public const IGNORED_DIRS = [
        'vendor', 'node_modules', '.git', '.github', '.idea', '.vscode', '.fleet',
        'storage', 'bootstrap/cache', 'public/build', 'public/vendor', 'public/storage',
        'public/hot', 'dist', 'coverage', '.phpunit.cache', '.nova', '.zed', '.docker',
    ];

    /** Arquivos idênticos ao esqueleto do `laravel new`. */
    public const FILES = [
        '.editorconfig', '.env.example', '.env', '.gitattributes', '.gitignore',
        '.styleci.yml', 'README.md', 'artisan', 'composer.json', 'composer.lock',
        'package.json', 'package-lock.json', 'pnpm-lock.yaml', 'yarn.lock',
        'phpunit.xml', 'phpunit.xml.dist', 'vite.config.js', 'vite.config.ts',
        'tailwind.config.js', 'postcss.config.js', 'compose.yml', 'docker-compose.yml',
        'Dockerfile', 'pint.json', 'phpstan.neon', 'rector.php',

        'app/Http/Controllers/Controller.php',
        'bootstrap/providers.php',

        'config/app.php', 'config/auth.php', 'config/cache.php', 'config/database.php',
        'config/filesystems.php', 'config/logging.php', 'config/mail.php',
        'config/queue.php', 'config/services.php', 'config/session.php',
        'config/livewire.php', 'config/permission.php', 'config/sanctum.php',
        'config/cors.php', 'config/broadcasting.php', 'config/hashing.php',
        'config/view.php', 'config/concurrency.php',

        'database/factories/UserFactory.php',
        'database/.gitignore',

        'public/index.php', 'public/favicon.ico', 'public/robots.txt', 'public/.htaccess',

        'resources/css/app.css',
        'resources/js/app.js', 'resources/js/bootstrap.js',
        'resources/views/welcome.blade.php',

        'routes/console.php', 'routes/channels.php',

        'tests/TestCase.php', 'tests/Pest.php', 'tests/CreatesApplication.php',
        'tests/Feature/ExampleTest.php', 'tests/Unit/ExampleTest.php',
    ];

    /** Padrões de arquivos gerados/publicados por pacotes. */
    public const PATTERNS = [
        '#^database/migrations/0001_01_01_\d+_.*\.php$#',
        '#^database/migrations/\d{4}_\d{2}_\d{2}_\d{6}_create_(permission_tables|personal_access_tokens_table|notifications_table|cache_table|jobs_table|sessions_table|failed_jobs_table|password_reset_tokens_table)\.php$#',
        '#^resources/views/vendor/#',
        '#\.lock$#',
        '#^\.env\..*#',
    ];

    /**
     * Arquivos do esqueleto que quase sempre são customizados e que
     * amarram o fluxo do sistema: entram no mapa mesmo sendo "padrão".
     */
    public const ALWAYS_INCLUDE = [
        'routes/web.php',
        'routes/api.php',
        'bootstrap/app.php',
        'app/Providers/AppServiceProvider.php',
        'app/Models/User.php',
        'database/seeders/DatabaseSeeder.php',
        'resources/css/app.css',
        'resources/js/app.js',
        'tailwind.config.js',
    ];

    public static function isStock(string $rel): bool
    {
        if (in_array($rel, self::ALWAYS_INCLUDE, true)) {
            return false;
        }
        if (in_array($rel, self::FILES, true)) {
            return true;
        }
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $rel) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function inIgnoredDir(string $rel): bool
    {
        foreach (self::IGNORED_DIRS as $dir) {
            if ($rel === $dir || str_starts_with($rel, $dir . '/')) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Varre o projeto separando arquivos relevantes dos arquivos padrão.
 */
final class Scanner
{
    /** @var array<string,string> caminho relativo => sha1 do baseline */
    private array $baselineHashes = [];

    public function __construct(
        private string $root,
        private bool $includeStock = false,
        private ?string $baselineDir = null,
        private array $extraIgnore = [],
    ) {
        if ($this->baselineDir !== null) {
            $this->loadBaseline($this->baselineDir);
        }
    }

    /** @return array{relevantes: array<int,string>, padrao: array<int,string>, modificados: array<int,string>} */
    public function scan(): array
    {
        $relevantes = [];
        $padrao = [];
        $modificados = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $file): bool {
                    $rel = $this->relative($file->getPathname());

                    return ! Stock::inIgnoredDir($rel) && ! $this->isExtraIgnored($rel);
                }
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }

            $rel = $this->relative($file->getPathname());

            if (! $this->isAnalyzable($rel)) {
                continue;
            }

            $stock = Stock::isStock($rel);

            // Baseline por hash: se o arquivo existe no projeto limpo e mudou,
            // ele passa a ser "modificado" e entra no mapa.
            if (isset($this->baselineHashes[$rel])) {
                if ($this->baselineHashes[$rel] === sha1_file($file->getPathname())) {
                    $padrao[] = $rel;
                    continue;
                }
                $modificados[] = $rel;
                $relevantes[] = $rel;
                continue;
            }

            if ($stock && ! $this->includeStock) {
                $padrao[] = $rel;
                continue;
            }

            $relevantes[] = $rel;
        }

        sort($relevantes);
        sort($padrao);
        sort($modificados);

        return ['relevantes' => $relevantes, 'padrao' => $padrao, 'modificados' => $modificados];
    }

    private function isAnalyzable(string $rel): bool
    {
        return str_ends_with($rel, '.php')
            || str_ends_with($rel, '.blade.php')
            || str_ends_with($rel, '.js')
            || str_ends_with($rel, '.vue')
            || (preg_match('~\.s?css$~', $rel) === 1 && ! str_ends_with($rel, '.min.css'))
            || (str_ends_with($rel, '.json') && (str_starts_with($rel, 'lang/') || str_starts_with($rel, 'resources/lang/')));
    }

    private function isExtraIgnored(string $rel): bool
    {
        foreach ($this->extraIgnore as $pattern) {
            if ($pattern !== '' && fnmatch($pattern, $rel)) {
                return true;
            }
        }

        return false;
    }

    private function relative(string $absolute): string
    {
        // Normaliza os DOIS lados antes de comparar: no Windows, realpath()/
        // SplFileInfo devolvem caminhos com '\', então comparar um prefixo
        // com '/' já anexado (como antes) nunca batia e a "relativa" virava
        // o caminho absoluto inteiro — quebrando a leitura de todo arquivo.
        $absolutoNorm = str_replace('\\', '/', $absolute);
        $rootNorm = str_replace('\\', '/', rtrim($this->root, '/\\')) . '/';

        return str_starts_with($absolutoNorm, $rootNorm)
            ? substr($absolutoNorm, strlen($rootNorm))
            : $absolutoNorm;
    }

    private function loadBaseline(string $dir): void
    {
        if (! is_dir($dir)) {
            fwrite(STDERR, "Aviso: baseline '{$dir}' não encontrado, seguindo com a lista interna.\n");

            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $file) use ($dir): bool {
                    $rel = self::relativoA($file->getPathname(), $dir);

                    return ! Stock::inIgnoredDir($rel);
                }
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $rel = self::relativoA($file->getPathname(), $dir);
            $this->baselineHashes[$rel] = sha1_file($file->getPathname());
        }
    }

    /** Mesma normalização de relative(), como helper estático reaproveitável (baseline usa outra raiz). */
    private static function relativoA(string $absoluto, string $base): string
    {
        $absolutoNorm = str_replace('\\', '/', $absoluto);
        $baseNorm = str_replace('\\', '/', rtrim($base, '/\\')) . '/';

        return str_starts_with($absolutoNorm, $baseNorm)
            ? substr($absolutoNorm, strlen($baseNorm))
            : $absolutoNorm;
    }
}

/**
 * Leitor de arquivos PHP token a token (o mesmo lexer que o PHP usa para
 * compilar o arquivo). Extrai namespace, imports, classes, traits, métodos
 * e tudo que cada método referencia.
 */
final class PhpAnalyzer
{
    /** Helpers globais que interessam ao mapa de fluxo. */
    private const HELPERS = [
        'view', 'route', 'to_route', 'redirect', 'response', 'abort', 'abort_if', 'abort_unless',
        'dispatch', 'dispatch_sync', 'event', 'broadcast', 'now', 'auth', 'session', 'request',
        'config', 'cache', 'logger', 'old', 'back', 'validator', 'collect', 'trans', '__',
        'storage_path', 'public_path', 'asset', 'bcrypt', 'feature', 'policy', 'app', 'resolve',
        'report', 'rescue', 'retry', 'throw_if', 'throw_unless', 'str', 'blank', 'filled', 'tap',
    ];

    /** Funções cujo primeiro argumento string vale registrar. */
    private const CAPTURE_FIRST_STRING = [
        'view', 'route', 'to_route', 'config', 'trans', '__', 'event', 'feature',
        'can', 'cannot', 'hasrole', 'haspermissionto', 'hasanyrole', 'hasallroles',
        'authorize', 'allows', 'denies', 'middleware', 'dispatchto', 'dispatch',
    ];

    /** Métodos de relacionamento do Eloquent. */
    private const RELACIONAMENTOS = [
        'hasone', 'hasmany', 'belongsto', 'belongstomany', 'hasonethrough', 'hasmanythrough',
        'morphto', 'morphone', 'morphmany', 'morphtomany', 'morphedbymany',
    ];

    /** Chamadas ligadas a permissão/feature flag (Spatie, Gates, Pennant). */
    private const GATE_FUNCS = [
        'can', 'cannot', 'hasrole', 'haspermissionto', 'hasanyrole', 'hasallroles',
        'hasanypermission', 'authorize', 'allows', 'denies',
    ];

    public static function analyze(string $rel, string $code): array
    {
        $tokens = self::compact(\PhpToken::tokenize($code));
        $count = count($tokens);

        $file = [
            'rel' => $rel,
            'tipo' => 'php',
            'namespace' => '',
            'uses' => [],
            'classes' => [],
            'funcoes' => [],
            'linhas' => substr_count($code, "\n") + 1,
        ];

        $ns = '';
        $uses = [];
        $pendingAttrs = [];

        for ($i = 0; $i < $count; $i++) {
            $tok = $tokens[$i];

            if ($tok->id === T_NAMESPACE) {
                [$ns, $i] = self::readName($tokens, $i + 1);
                $file['namespace'] = $ns;
                continue;
            }

            if ($tok->id === T_USE) {
                $i = self::readUses($tokens, $i + 1, $uses);
                continue;
            }

            if ($tok->id === T_ATTRIBUTE) {
                [$attrs, $i] = self::readAttribute($tokens, $i);
                $pendingAttrs = array_merge($pendingAttrs, $attrs);
                continue;
            }

            if (in_array($tok->id, [T_CLASS, T_TRAIT, T_INTERFACE, T_ENUM], true)) {
                $prev = $tokens[$i - 1] ?? null;
                if ($prev !== null && ($prev->text === '::' || $prev->id === T_NEW)) {
                    continue; // ::class ou classe anônima
                }
                [$class, $i] = self::readClass($tokens, $i, $ns, $uses, $pendingAttrs, $rel);
                $pendingAttrs = [];
                if ($class !== null) {
                    $file['classes'][] = $class;
                }
                continue;
            }

            if ($tok->id === T_FUNCTION && ($tokens[$i + 1]->id ?? 0) === T_STRING) {
                [$fn, $i] = self::readFunction($tokens, $i, $ns, $uses, [], [], []);
                $file['funcoes'][] = $fn;
                continue;
            }
        }

        $file['uses'] = $uses;

        return $file;
    }

    // ------------------------------------------------------------------
    // Leitura estrutural
    // ------------------------------------------------------------------

    /** @param array<int,\PhpToken> $tokens */
    public static function readClass(array $tokens, int $i, string $ns, array $uses, array $attrs, string $rel): array
    {
        $kindMap = [T_CLASS => 'class', T_TRAIT => 'trait', T_INTERFACE => 'interface', T_ENUM => 'enum'];
        $kind = $kindMap[$tokens[$i]->id];
        $line = $tokens[$i]->line;
        $count = count($tokens);

        $i++;
        if (($tokens[$i]->id ?? 0) !== T_STRING) {
            return [null, $i];
        }

        $name = $tokens[$i]->text;
        $extends = [];
        $implements = [];
        $mode = null;

        for ($i++; $i < $count && $tokens[$i]->text !== '{'; $i++) {
            $tok = $tokens[$i];
            if ($tok->id === T_EXTENDS) {
                $mode = 'extends';
                continue;
            }
            if ($tok->id === T_IMPLEMENTS) {
                $mode = 'implements';
                continue;
            }
            if (self::isName($tok) && $mode !== null) {
                [$fq, $i] = self::readName($tokens, $i);
                $resolved = self::resolve($fq, $ns, $uses);
                $mode === 'extends' ? $extends[] = $resolved : $implements[] = $resolved;
            }
        }

        $class = [
            'nome' => $name,
            'fqcn' => $ns !== '' ? $ns . '\\' . $name : $name,
            'kind' => $kind,
            'linha' => $line,
            'arquivo' => $rel,
            'extends' => $extends,
            'implements' => $implements,
            'traits' => [],
            'atributos' => $attrs,
            'propriedades' => [],
            'constantes' => [],
            'metodos' => [],
        ];

        if (($tokens[$i]->text ?? '') !== '{') {
            return [$class, $i];
        }

        $i++; // entra no corpo
        $depth = 1;
        $mods = [];
        $typeBuf = '';
        $memberAttrs = [];

        while ($i < $count && $depth > 0) {
            $tok = $tokens[$i];

            if ($tok->text === '}') {
                $depth--;
                $i++;
                continue;
            }
            if ($tok->text === '{') {
                $depth++;
                $i++;
                continue;
            }

            if ($tok->id === T_ATTRIBUTE) {
                [$a, $i] = self::readAttribute($tokens, $i);
                $memberAttrs = array_merge($memberAttrs, $a);
                $i++;
                continue;
            }

            if (in_array($tok->id, [T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC, T_ABSTRACT, T_FINAL, T_VAR], true)
                || strtolower($tok->text) === 'readonly') {
                $mods[] = strtolower($tok->text);
                $i++;
                continue;
            }

            // use TraitA, TraitB;
            if ($tok->id === T_USE) {
                for ($i++; $i < $count && $tokens[$i]->text !== ';' && $tokens[$i]->text !== '{'; $i++) {
                    if (self::isName($tokens[$i])) {
                        [$fq, $i] = self::readName($tokens, $i);
                        $class['traits'][] = self::resolve($fq, $ns, $uses);
                    }
                }
                if (($tokens[$i]->text ?? '') === '{') { // bloco de conflito
                    $inner = 1;
                    for ($i++; $i < $count && $inner > 0; $i++) {
                        if ($tokens[$i]->text === '{') {
                            $inner++;
                        }
                        if ($tokens[$i]->text === '}') {
                            $inner--;
                        }
                    }
                }
                $i++;
                $mods = [];
                continue;
            }

            if ($tok->id === T_CONST) {
                $names = [];
                for ($i++; $i < $count && $tokens[$i]->text !== ';'; $i++) {
                    if ($tokens[$i]->id === T_STRING && ($tokens[$i + 1]->text ?? '') === '=') {
                        $names[] = $tokens[$i]->text;
                    }
                }
                $class['constantes'] = array_merge($class['constantes'], $names);
                $i++;
                $mods = [];
                $typeBuf = '';
                continue;
            }

            if ($tok->id === T_FUNCTION) {
                [$method, $i] = self::readFunction($tokens, $i, $ns, $uses, $mods, $class['propriedades'], $memberAttrs, $class['fqcn']);
                $class['metodos'][] = $method;
                $mods = [];
                $typeBuf = '';
                $memberAttrs = [];
                continue;
            }

            if ($tok->id === T_VARIABLE) {
                $class['propriedades'][ltrim($tok->text, '$')] = [
                    'tipo' => $typeBuf !== '' ? self::resolve($typeBuf, $ns, $uses) : null,
                    'modificadores' => $mods,
                    'linha' => $tok->line,
                ];
                for (; $i < $count && $tokens[$i]->text !== ';'; $i++) {
                    if ($tokens[$i]->text === '{') { // property hooks
                        $inner = 1;
                        for ($i++; $i < $count && $inner > 0; $i++) {
                            if ($tokens[$i]->text === '{') {
                                $inner++;
                            }
                            if ($tokens[$i]->text === '}') {
                                $inner--;
                            }
                        }
                        break;
                    }
                }
                $i++;
                $mods = [];
                $typeBuf = '';
                $memberAttrs = [];
                continue;
            }

            if (self::isName($tok) || $tok->id === T_ARRAY) {
                if ($typeBuf === '') {
                    $typeBuf = $tok->text;
                }
                $i++;
                continue;
            }
            if (in_array($tok->text, ['?', '|', '&'], true)) {
                $i++;
                continue;
            }

            $i++;
        }

        return [$class, $i];
    }

    /** @param array<int,\PhpToken> $tokens */
    public static function readFunction(
        array $tokens,
        int $i,
        string $ns,
        array $uses,
        array $mods,
        array $props,
        array $attrs,
        string $ownerFqcn = ''
    ): array {
        $count = count($tokens);
        $i++; // 'function'
        if (($tokens[$i]->text ?? '') === '&') {
            $i++;
        }

        $name = $tokens[$i]->text ?? '{closure}';
        $line = $tokens[$i]->line;
        $i++;

        $params = [];
        $paramTypes = [];
        $nullable = false;
        if (($tokens[$i]->text ?? '') === '(') {
            $depth = 0;
            $typeBuf = '';
            for (; $i < $count; $i++) {
                $tok = $tokens[$i];
                if ($tok->text === '(') {
                    $depth++;
                    continue;
                }
                if ($tok->text === ')') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                    continue;
                }
                if ($depth !== 1) {
                    continue;
                }
                if ($tok->text === ',') {
                    $typeBuf = '';
                    $nullable = false;
                    continue;
                }
                if ($tok->text === '?') {
                    $nullable = true;
                    continue;
                }
                if ($tok->id === T_VARIABLE) {
                    $tipo = $typeBuf !== '' ? self::resolve($typeBuf, $ns, $uses) : null;
                    $params[] = ['nome' => $tok->text, 'tipo' => $tipo, 'nullable' => $nullable ?? false];
                    if ($tipo !== null) {
                        $paramTypes[$tok->text] = $tipo;
                    }
                    $typeBuf = '';
                    $nullable = false;
                    continue;
                }
                if (self::isName($tok) || $tok->id === T_ARRAY || $tok->id === T_STATIC) {
                    if ($typeBuf === '') {
                        $typeBuf = $tok->text;
                    }
                }
            }
        }

        // tipo de retorno
        $retorno = null;
        $j = $i + 1;
        if (($tokens[$j]->text ?? '') === ':') {
            $buf = '';
            for ($j++; $j < $count && $tokens[$j]->text !== '{' && $tokens[$j]->text !== ';'; $j++) {
                $buf .= $tokens[$j]->text;
            }
            $retorno = $buf;
            $i = $j - 1;
        }

        $method = [
            'nome' => $name,
            'linha' => $line,
            'modificadores' => $mods,
            'parametros' => $params,
            'retorno' => $retorno,
            'atributos' => $attrs,
            'refs' => self::emptyRefs(),
            'abstrato' => false,
        ];

        // corpo
        for ($i++; $i < $count && $tokens[$i]->text !== '{' && $tokens[$i]->text !== ';'; $i++) {
            // pula até o corpo
        }

        if (($tokens[$i]->text ?? '') === ';') {
            $method['abstrato'] = true;

            return [$method, $i + 1];
        }

        $start = $i;
        $end = self::matchBrace($tokens, $start);
        $body = array_slice($tokens, $start + 1, max(0, $end - $start - 1));

        $method['refs'] = self::analyzeBody($body, $ns, $uses, $props, $paramTypes, $ownerFqcn);

        return [$method, $end + 1];
    }

    // ------------------------------------------------------------------
    // Leitura do corpo de um método
    // ------------------------------------------------------------------

    /** @param array<int,\PhpToken> $s */
    public static function analyzeBody(array $s, string $ns, array $uses, array $props, array $varTypes, string $owner): array
    {
        $refs = self::emptyRefs();
        $count = count($s);

        for ($i = 0; $i < $count; $i++) {
            $tok = $s[$i];
            $next = $s[$i + 1] ?? null;

            // $var = new Classe(...)   |   $var = Classe::...
            if ($tok->id === T_VARIABLE && ($next?->text === '=')) {
                if (($s[$i + 2]->id ?? 0) === T_NEW && self::isName($s[$i + 3] ?? null)) {
                    [$fq] = self::readName($s, $i + 3);
                    $varTypes[$tok->text] = self::resolve($fq, $ns, $uses);
                } elseif (self::isName($s[$i + 2] ?? null) && ($s[$i + 3]->text ?? '') === '::') {
                    [$fq] = self::readName($s, $i + 2);
                    $varTypes[$tok->text] = self::resolve($fq, $ns, $uses);
                }
            }

            // $this->algo(...) | $this->prop->algo(...)
            if ($tok->text === '$this' && $next?->text === '->') {
                $member = $s[$i + 2]->text ?? '';

                if (($s[$i + 3]->text ?? '') === '(') {
                    if (in_array(strtolower($member), self::RELACIONAMENTOS, true) && self::isName($s[$i + 4] ?? null)) {
                        [$rel] = self::readName($s, $i + 4);
                        $refs['relacionamentos'][] = [
                            'tipo' => $member,
                            'classe' => self::resolve($rel, $ns, $uses, $owner),
                        ];
                    }
                    self::recordSelfCall($refs, $member, $s, $i + 3, $tok->line);
                    continue;
                }

                $tipo = $props[$member]['tipo'] ?? null;
                if ($tipo !== null) {
                    $refs['propriedades'][$member] = $tipo;
                    if (($s[$i + 3]->text ?? '') === '->' && ($s[$i + 5]->text ?? '') === '(') {
                        $metodoChain = $s[$i + 4]->text ?? '';
                        self::push($refs['chamadas_instancia'], $tipo, $metodoChain);
                        $refs['sequencia'][] = ['tipo' => 'instancia', 'classe' => $tipo, 'metodo' => $metodoChain, 'linha' => $tok->line];
                    }
                }
                continue;
            }

            // Classe::metodo(...) / Classe::class / Enum::CASO
            if (self::isName($tok) && ($next?->text === '::')) {
                $prev = $s[$i - 1] ?? null;
                if ($prev !== null && ($prev->text === '->' || $prev->text === '::')) {
                    continue;
                }
                [$fq, $skip] = self::readName($s, $i);
                $fqcn = self::resolve($fq, $ns, $uses, $owner);
                $member = $s[$skip + 2]->text ?? '';

                if (strtolower($member) === 'class') {
                    self::push($refs['referencias_classe'], $fqcn, '::class');
                } else {
                    if (strtolower($member) === 'component' && str_ends_with($fqcn, 'Livewire')) {
                        $alias = self::firstString($s, $skip + 3);
                        if ($alias !== null && self::isName($s[$skip + 6] ?? null)) {
                            [$alvo] = self::readName($s, $skip + 6);
                            $refs['componentes_livewire'][$alias] = self::resolve($alvo, $ns, $uses, $owner);
                        }
                    }
                    self::push($refs['chamadas_estaticas'], $fqcn, $member);
                    if (in_array(strtolower($member), ['dispatch', 'dispatchsync', 'dispatchafterresponse'], true)) {
                        $refs['jobs'][] = $fqcn;
                        $refs['gatilhos'][] = [
                            'tipo' => 'Job dispatch',
                            'alvo' => $fqcn,
                            'params' => self::argsSource($s, $skip + 3, 0),
                            'linha' => $tok->line,
                        ];
                        $refs['sequencia'][] = ['tipo' => 'job', 'classe' => $fqcn, 'linha' => $tok->line];
                    } else {
                        $refs['sequencia'][] = ['tipo' => 'estatica', 'classe' => $fqcn, 'metodo' => $member, 'linha' => $tok->line];
                    }
                }
                $i = $skip;
                continue;
            }

            // new Classe(...)
            if ($tok->id === T_NEW && self::isName($s[$i + 1] ?? null)) {
                [$fq, $skip] = self::readName($s, $i + 1);
                $fqcn = self::resolve($fq, $ns, $uses, $owner);
                self::push($refs['instanciacoes'], $fqcn, 'new');
                $refs['sequencia'][] = ['tipo' => 'new', 'classe' => $fqcn, 'linha' => $tok->line];
                $i = $skip;
                continue;
            }

            // $var->metodo(...) com tipo conhecido
            if ($tok->id === T_VARIABLE && $next?->text === '->' && ($s[$i + 3]->text ?? '') === '(') {
                $metodo = $s[$i + 2]->text ?? '';
                if (isset($varTypes[$tok->text])) {
                    self::push($refs['chamadas_instancia'], $varTypes[$tok->text], $metodo);
                    $refs['sequencia'][] = ['tipo' => 'instancia', 'classe' => $varTypes[$tok->text], 'metodo' => $metodo, 'linha' => $tok->line];
                }
                if (in_array(strtolower($metodo), self::GATE_FUNCS, true)) {
                    $arg = self::firstString($s, $i + 3);
                    if ($arg !== null) {
                        $refs['permissoes'][] = $arg;
                    }
                }
                continue;
            }

            // helper(...) / funcao_global(...)
            if (self::isName($tok) && $next?->text === '(') {
                $prev = $s[$i - 1] ?? null;
                if ($prev !== null && in_array($prev->text, ['->', '::', '?->'], true)) {
                    $lower = strtolower($tok->text);
                    if (in_array($lower, self::GATE_FUNCS, true)) {
                        $arg = self::firstString($s, $i + 1);
                        if ($arg !== null) {
                            $refs['permissoes'][] = $arg;
                        }
                    }
                    continue;
                }
                if ($prev !== null && in_array($prev->id, [T_NEW, T_FUNCTION], true)) {
                    continue;
                }

                $lower = strtolower($tok->text);
                $arg = in_array($lower, self::CAPTURE_FIRST_STRING, true) ? self::firstString($s, $i + 1) : null;

                if (in_array($lower, self::HELPERS, true)) {
                    $refs['helpers'][$lower] = array_values(array_unique(array_merge(
                        $refs['helpers'][$lower] ?? [],
                        $arg !== null ? [$arg] : []
                    )));
                } elseif (! self::funcaoInterna($tok->text)) {
                    $refs['funcoes_globais'][] = $tok->text;
                }

                if ($arg !== null) {
                    match ($lower) {
                        'view' => $refs['views'][] = $arg,
                        'route', 'to_route' => $refs['rotas'][] = $arg,
                        'event' => $refs['eventos'][] = $arg,
                        'feature' => $refs['permissoes'][] = 'feature:' . $arg,
                        default => null,
                    };
                    if ($lower === 'event') {
                        $refs['gatilhos'][] = [
                            'tipo' => 'Event',
                            'alvo' => $arg,
                            'params' => self::argsSource($s, $i + 1, 1),
                            'linha' => $tok->line,
                        ];
                        $refs['sequencia'][] = ['tipo' => 'evento', 'evento' => $arg, 'linha' => $tok->line];
                    }
                    if ($lower === 'view') {
                        $refs['sequencia'][] = ['tipo' => 'view', 'view' => $arg, 'linha' => $tok->line];
                    }
                }

                if (in_array($lower, ['redirect', 'response', 'abort', 'to_route'], true)) {
                    $refs['sequencia'][] = ['tipo' => 'resposta', 'nome' => $lower, 'linha' => $tok->line];
                }

                if (in_array($lower, self::GATE_FUNCS, true) && $arg !== null) {
                    $refs['permissoes'][] = $arg;
                }
                continue;
            }
        }

        foreach (['chamadas_internas', 'jobs', 'eventos', 'views', 'rotas', 'permissoes', 'funcoes_globais', 'eventos_livewire'] as $k) {
            $refs[$k] = array_values(array_unique($refs[$k]));
        }

        return $refs;
    }

    private static function recordSelfCall(array &$refs, string $member, array $s, int $parenIndex, int $line): void
    {
        $lower = strtolower($member);

        if ($lower === 'dispatch' || $lower === 'dispatchself' || $lower === 'dispatchto') {
            $arg = self::firstString($s, $parenIndex);
            if ($arg !== null) {
                $refs['eventos_livewire'][] = $arg;
                $refs['gatilhos'][] = [
                    'tipo' => 'Livewire dispatch',
                    'alvo' => $arg,
                    'params' => self::argsSource($s, $parenIndex, 1),
                    'linha' => $line,
                ];
                $refs['sequencia'][] = ['tipo' => 'dispatch_evento', 'evento' => $arg, 'linha' => $line];

                return;
            }
        }

        if (in_array($lower, self::GATE_FUNCS, true)) {
            $arg = self::firstString($s, $parenIndex);
            if ($arg !== null) {
                $refs['permissoes'][] = $arg;
            }
        }

        if ($lower === 'validate' || $lower === 'validateonly') {
            $refs['valida'] = true;
            $refs['sequencia'][] = ['tipo' => 'validacao', 'linha' => $line];
            $refs['chamadas_internas'][] = $member;
            $refs['linhas_chamadas'][$member] ??= $line;

            return;
        }

        $refs['chamadas_internas'][] = $member;
        $refs['linhas_chamadas'][$member] ??= $line;
        $refs['sequencia'][] = ['tipo' => 'self', 'metodo' => $member, 'linha' => $line];
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    public static function emptyRefs(): array
    {
        return [
            'chamadas_internas' => [],
            'chamadas_estaticas' => [],   // fqcn => [métodos]
            'chamadas_instancia' => [],   // fqcn => [métodos]
            'instanciacoes' => [],
            'referencias_classe' => [],
            'propriedades' => [],
            'helpers' => [],
            'funcoes_globais' => [],
            'views' => [],
            'rotas' => [],
            'eventos' => [],
            'eventos_livewire' => [],
            'jobs' => [],
            'permissoes' => [],
            'relacionamentos' => [],
            'componentes_livewire' => [],
            'gatilhos' => [],
            'sequencia' => [],   // passos em ORDEM de execucao, para o diagrama de sequencia
            'linhas_chamadas' => [],
            'valida' => false,
        ];
    }

    private static function push(array &$bucket, string $key, string $value): void
    {
        $bucket[$key] ??= [];
        if ($value !== '' && ! in_array($value, $bucket[$key], true)) {
            $bucket[$key][] = $value;
        }
    }

    /** Primeiro argumento string a partir do '(' indicado. */
    public static function firstString(array $s, int $parenIndex): ?string
    {
        $tok = $s[$parenIndex + 1] ?? null;
        if ($tok === null) {
            return null;
        }
        if ($tok->id === T_CONSTANT_ENCAPSED_STRING) {
            return trim($tok->text, "'\"");
        }

        return null;
    }

    /** Ignora funções nativas do PHP (array_map, count...) — só interessa o código do projeto. */
    private static function funcaoInterna(string $nome): bool
    {
        static $cache = [];
        $key = strtolower($nome);
        if (! array_key_exists($key, $cache)) {
            $cache[$key] = function_exists($key) && (new \ReflectionFunction($key))->isInternal();
        }

        return $cache[$key];
    }

    public static function isName(?\PhpToken $tok): bool
    {
        return $tok !== null && in_array($tok->id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true);
    }

    /** @return array{0:string,1:int} nome lido e índice do último token consumido */
    public static function readName(array $tokens, int $i): array
    {
        $buf = '';
        $count = count($tokens);
        for (; $i < $count; $i++) {
            $tok = $tokens[$i];
            if (self::isName($tok) || $tok->text === '\\') {
                $buf .= $tok->text;
                continue;
            }
            break;
        }

        return [$buf, $i - 1];
    }

    public static function readUses(array $tokens, int $i, array &$uses): int
    {
        $count = count($tokens);
        $buf = '';
        $alias = null;
        $prefix = '';
        $mode = 'name';

        $flush = function () use (&$buf, &$alias, &$prefix, &$uses, &$mode): void {
            if ($buf === '') {
                return;
            }
            $fqcn = $prefix !== '' ? rtrim($prefix, '\\') . '\\' . ltrim($buf, '\\') : ltrim($buf, '\\');
            $parts = explode('\\', $fqcn);
            $key = strtolower($alias ?? end($parts));
            $uses[$key] = $fqcn;
            $buf = '';
            $alias = null;
            $mode = 'name';
        };

        for (; $i < $count; $i++) {
            $tok = $tokens[$i];
            if ($tok->text === ';') {
                break;
            }
            if ($tok->id === T_AS) {
                $mode = 'alias';
                continue;
            }
            if ($tok->text === '{') {
                $prefix = $buf;
                $buf = '';
                continue;
            }
            if ($tok->text === '}') {
                $flush();
                $prefix = '';
                continue;
            }
            if ($tok->text === ',') {
                $flush();
                continue;
            }
            if (self::isName($tok) || $tok->text === '\\') {
                $mode === 'alias' ? $alias = $tok->text : $buf .= $tok->text;
            }
        }
        $flush();

        return $i;
    }

    /** @return array{0:array<int,array{nome:string,arg:?string,raw:string}>,1:int} */
    public static function readAttribute(array $tokens, int $i): array
    {
        $count = count($tokens);
        $depth = 1;
        $raw = '';
        $attrs = [];
        $currentName = null;
        $currentArg = null;

        for ($i++; $i < $count && $depth > 0; $i++) {
            $tok = $tokens[$i];
            if ($tok->text === '[' || $tok->text === '(') {
                $depth++;
            }
            if ($tok->text === ']' || $tok->text === ')') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
            $raw .= $tok->text;

            if (self::isName($tok) && $currentName === null) {
                $currentName = $tok->text;
                continue;
            }
            if ($tok->id === T_CONSTANT_ENCAPSED_STRING && $currentArg === null) {
                $currentArg = trim($tok->text, "'\"");
            }
            if ($tok->text === ',' && $depth === 1 && $currentName !== null) {
                $attrs[] = ['nome' => $currentName, 'arg' => $currentArg, 'raw' => $raw];
                $currentName = null;
                $currentArg = null;
            }
        }

        if ($currentName !== null) {
            $attrs[] = ['nome' => $currentName, 'arg' => $currentArg, 'raw' => $raw];
        }

        return [$attrs, $i];
    }

    public static function matchBrace(array $tokens, int $start): int
    {
        $count = count($tokens);
        $depth = 0;
        for ($i = $start; $i < $count; $i++) {
            $tok = $tokens[$i];
            if ($tok->text === '{' || in_array($tok->id, [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) {
                $depth++;
                continue;
            }
            if ($tok->text === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return $count - 1;
    }

    /**
     * Texto-fonte de cada argumento top-level de uma chamada, a partir do
     * indice do '(' de abertura. $skip pula os N primeiros argumentos
     * (ex.: 1 para pular o nome do evento e pegar so os parametros extras).
     */
    public static function argsSource(array $s, int $parenIndex, int $skip = 0): array
    {
        if (($s[$parenIndex]->text ?? '') !== '(') {
            return [];
        }

        $count = count($s);
        $depth = 0;
        $args = [];
        $atual = [];
        $indice = 0;

        for ($i = $parenIndex; $i < $count; $i++) {
            $tok = $s[$i];

            if ($tok->text === '(' || $tok->text === '[') {
                $depth++;
                if ($i === $parenIndex) {
                    continue;
                }
            } elseif ($tok->text === ')' || $tok->text === ']') {
                $depth--;
                if ($depth === 0 && $tok->text === ')') {
                    break;
                }
            }

            if ($depth === 1 && $tok->text === ',') {
                if ($indice >= $skip && $atual !== []) {
                    $args[] = self::juntar($atual);
                }
                $atual = [];
                $indice++;
                continue;
            }

            $atual[] = $tok;
        }
        if ($indice >= $skip && $atual !== []) {
            $args[] = self::juntar($atual);
        }

        return $args;
    }

    private static function juntar(array $tokens): string
    {
        $out = '';
        $prevChar = '';
        foreach ($tokens as $tok) {
            $texto = $tok->text;
            $primeiro = $texto[0] ?? '';
            if ($prevChar !== '' && self::ehPalavra($prevChar) && self::ehPalavra($primeiro)) {
                $out .= ' ';
            }
            $out .= $texto;
            $prevChar = substr($texto, -1) ?: '';
        }

        return trim($out);
    }

    private static function ehPalavra(string $c): bool
    {
        return $c !== '' && (ctype_alnum($c) || $c === '_' || $c === '$');
    }

    public static function resolve(string $name, string $ns, array $uses, string $owner = ''): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        $lower = strtolower($name);

        if (in_array($lower, ['self', 'static', '$this'], true)) {
            return $owner !== '' ? $owner : $name;
        }
        if (in_array($lower, ['parent', 'int', 'float', 'string', 'bool', 'array', 'void', 'mixed', 'object', 'callable', 'iterable', 'null', 'never', 'true', 'false'], true)) {
            return $name;
        }
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }

        $parts = explode('\\', $name);
        $first = strtolower($parts[0]);
        if (isset($uses[$first])) {
            $parts[0] = $uses[$first];

            return implode('\\', $parts);
        }

        return $ns !== '' ? $ns . '\\' . $name : $name;
    }

    /** @param array<int,\PhpToken> $tokens @return array<int,\PhpToken> */
    public static function compact(array $tokens): array
    {
        $out = [];
        foreach ($tokens as $tok) {
            if ($tok->id === T_WHITESPACE || $tok->id === T_COMMENT || $tok->id === T_DOC_COMMENT || $tok->id === T_INLINE_HTML) {
                continue;
            }
            $out[] = $tok;
        }

        return $out;
    }
}

/**
 * Lê uma view .blade.php e extrai o que ela aciona:
 * componentes filhos, includes, ações wire:, rotas e gates.
 */
final class BladeAnalyzer
{
    public static function analyze(string $rel, string $code): array
    {
        $view = [
            'rel' => $rel,
            'tipo' => 'blade',
            'view' => self::viewName($rel),
            'extends' => null,
            'secoes' => [],
            'includes' => [],
            'componentes' => [],      // <x-...>
            'livewire' => [],         // @livewire / <livewire:...>
            'acoes' => [],            // wire:click="salvar" => método
            'models_wire' => [],      // wire:model="regras.0.campo"
            'wire_js' => [],          // $wire.metodo()
            'rotas' => [],
            'permissoes' => [],
            'classes_php' => [],      // Classe:: dentro de @php ou expressões
            'diretivas_fluxo' => [],  // @foreach/@if etc (contagem)
            'elementos' => [],        // <button> e <a> encontrados, com a acao que disparam
            'linhas' => substr_count($code, "\n") + 1,
        ];

        if (preg_match('/@extends\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code, $m)) {
            $view['extends'] = $m[1];
        }

        foreach (self::all('/@(?:includeIf|includeWhen|includeUnless|includeFirst|include|each)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code) as $hit) {
            $view['includes'][] = $hit;
        }

        foreach (self::all('/@section\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code) as $hit) {
            $view['secoes'][] = '@section ' . $hit;
        }
        foreach (self::all('/@yield\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code) as $hit) {
            $view['secoes'][] = '@yield ' . $hit;
        }
        foreach (self::all('/@(?:push|prepend)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code) as $hit) {
            $view['secoes'][] = '@push ' . $hit;
        }

        foreach (self::all('/@livewire\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code) as $hit) {
            $view['livewire'][] = $hit;
        }
        foreach (self::all('/<livewire:([A-Za-z0-9._:-]+)/', $code) as $hit) {
            $view['livewire'][] = $hit;
        }

        foreach (self::all('/<x-([A-Za-z0-9._:-]+)/', $code) as $hit) {
            $view['componentes'][] = $hit;
        }

        // wire:click="salvar", wire:submit.prevent="salvar", wire:click="remove({{ $i }})"
        $pattern = '/wire:([a-zA-Z0-9_.:-]+)\s*=\s*"([^"]*)"/';
        if (preg_match_all($pattern, $code, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $dir = strtolower(explode('.', $match[1])[0]);
                $val = trim($match[2]);

                if (in_array($dir, ['model', 'modelable', 'ignore', 'key', 'loading', 'target', 'transition', 'dirty', 'offline', 'stream', 'text', 'show', 'cloak'], true)) {
                    if ($val !== '' && ($dir === 'model' || $dir === 'modelable')) {
                        $view['models_wire'][] = $val;
                    }
                    continue;
                }

                if (preg_match('/^\$?([A-Za-z_][A-Za-z0-9_]*)\s*(\(|$)/', $val, $mm)) {
                    $view['acoes'][] = ['evento' => 'wire:' . $match[1], 'metodo' => $mm[1], 'expressao' => $val];
                } elseif ($val !== '') {
                    $view['acoes'][] = ['evento' => 'wire:' . $match[1], 'metodo' => null, 'expressao' => $val];
                }
            }
        }

        foreach (self::all('/\$wire\.([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $code) as $hit) {
            $view['wire_js'][] = $hit;
        }
        foreach (self::all('/\$wire\.call\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code) as $hit) {
            $view['wire_js'][] = $hit;
        }

        foreach (self::all('/route\s*\(\s*[\'"]([^\'"]+)[\'"]/', $code) as $hit) {
            $view['rotas'][] = $hit;
        }

        foreach (self::all('/@(?:can|cannot|canany|role|hasrole|hasanyrole|haspermission)\s*\(\s*[\'"]([^\'"]+)[\'"]/i', $code) as $hit) {
            $view['permissoes'][] = $hit;
        }

        foreach (self::all('/\b([A-Z][A-Za-z0-9_]*(?:\\\\[A-Z][A-Za-z0-9_]*)*)::/', $code) as $hit) {
            $view['classes_php'][] = $hit;
        }

        foreach (['foreach', 'forelse', 'if', 'unless', 'auth', 'guest', 'error', 'isset', 'switch', 'while', 'for'] as $directive) {
            $n = preg_match_all('/@' . $directive . '\b/', $code);
            if ($n > 0) {
                $view['diretivas_fluxo'][$directive] = $n;
            }
        }

        foreach (['includes', 'componentes', 'livewire', 'rotas', 'permissoes', 'classes_php', 'wire_js', 'models_wire', 'secoes'] as $k) {
            $view[$k] = array_values(array_unique($view[$k]));
        }

        $view['elementos'] = self::interativos($code);

        return $view;
    }

    /**
     * Encontra <button> e <a> e resolve o que cada um aciona: metodo Livewire
     * (wire:click), Alpine (@click/x-on:click), rota (href="{{ route(...) }}")
     * ou link cru.
     */
    private static function interativos(string $code): array
    {
        $elementos = [];

        foreach ([['button', '/<button\b([^>]*)>(.*?)<\/button>/is'], ['a', '/<a\b([^>]*)>(.*?)<\/a>/is']] as [$tipo, $padrao]) {
            if (! preg_match_all($padrao, $code, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches as $m) {
                $attrs = $m[1][0];
                $inner = $m[2][0];
                $linha = substr_count(substr($code, 0, (int) $m[0][1]), "\n") + 1;

                [$acao, $tipoAcao] = self::acaoDoElemento($attrs);

                $texto = trim((string) preg_replace('/\s+/', ' ', strip_tags($inner)));
                if (strlen($texto) > 60) {
                    $texto = substr($texto, 0, 59) . '…';
                }

                $elementos[] = [
                    'tipo' => $tipo === 'a' ? 'link' : 'button',
                    'texto' => $texto,
                    'acao' => $acao,
                    'tipo_acao' => $tipoAcao,
                    'linha' => $linha,
                ];
            }
        }

        usort($elementos, fn ($a, $b) => $a['linha'] <=> $b['linha']);

        return $elementos;
    }

    /** @return array{0:string,1:string} [acao, tipo_acao] onde tipo_acao ∈ metodo|rota|js|href|nenhuma */
    private static function acaoDoElemento(string $attrs): array
    {
        if (preg_match('/wire:click(?:\.[a-zA-Z]+)*\s*=\s*"([^"]*)"/', $attrs, $m)) {
            $expr = trim($m[1]);
            if (preg_match('/^\$?([A-Za-z_][A-Za-z0-9_]*)\s*(\(|$)/', $expr, $mm)) {
                return [$mm[1], 'metodo'];
            }

            return [$expr, 'js'];
        }

        if (preg_match('/(?:@click|x-on:click)\s*=\s*"([^"]*)"/', $attrs, $m)) {
            $expr = trim($m[1]);
            if (preg_match('/\$wire\.(?:call\(\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]|([A-Za-z_][A-Za-z0-9_]*)\s*\()/', $expr, $mm)) {
                return [$mm[1] !== '' ? $mm[1] : $mm[2], 'metodo'];
            }

            return [$expr, 'js'];
        }

        if (preg_match('/\bhref\s*=\s*"([^"]*)"/', $attrs, $m)) {
            $href = trim($m[1]);
            if (preg_match('/route\s*\(\s*[\'"]([^\'"]+)[\'"]/', $href, $mm)) {
                return [$mm[1], 'rota'];
            }
            if ($href === '' || $href === '#' || $href === 'javascript:void(0)' || $href === 'javascript:;') {
                return ['(sem destino — provavelmente JS)', 'nenhuma'];
            }

            return [$href, 'href'];
        }

        if (preg_match('/\btype\s*=\s*"submit"/', $attrs)) {
            return ['envia o formulario (wire:submit do <form> pai)', 'nenhuma'];
        }

        return ['sem acao identificada', 'nenhuma'];
    }

    public static function viewName(string $rel): string
    {
        $name = preg_replace('#^resources/views/#', '', $rel);
        $name = preg_replace('#\.blade\.php$#', '', (string) $name);

        return str_replace('/', '.', (string) $name);
    }

    private static function all(string $pattern, string $subject): array
    {
        return preg_match_all($pattern, $subject, $m) ? $m[1] : [];
    }
}

/**
 * Extrai os pontos de entrada declarados em routes/*.php,
 * respeitando prefixos e middlewares herdados de Route::group().
 */
final class RouteAnalyzer
{
    private const NAO_VERBOS = [
        'group', 'middleware', 'prefix', 'name', 'controller', 'domain',
        'namespace', 'scopebindings', 'withoutmiddleware', 'as', 'where',
    ];

    public static function analyze(string $rel, string $code, string $ns, array $uses): array
    {
        $rotas = [];
        $grupos = self::grupos($code);

        if (! preg_match_all('/Route::([a-zA-Z]+)\s*\(/', $code, $m, PREG_OFFSET_CAPTURE)) {
            return $rotas;
        }

        foreach ($m[1] as $k => $hit) {
            $verbo = strtolower($hit[0]);
            if (in_array($verbo, self::NAO_VERBOS, true)) {
                continue;
            }

            $abre = $m[0][$k][1] + strlen($m[0][$k][0]) - 1;
            $fecha = self::matchParen($code, $abre);
            if ($fecha === null) {
                continue;
            }

            $args = substr($code, $abre + 1, $fecha - $abre - 1);
            $fimCadeia = strpos($code, ';', $fecha);
            $chain = substr($code, $fecha + 1, ($fimCadeia === false ? strlen($code) : $fimCadeia) - $fecha - 1);
            if (str_contains($chain, '->group')) {
                $chain = substr($chain, 0, (int) strpos($chain, '->group'));
            }

            $linha = substr_count(substr($code, 0, $abre), "\n") + 1;
            $uri = preg_match('/^\s*[\'"]([^\'"]*)[\'"]/', $args, $u) ? $u[1] : trim($args);

            $alvoClasse = null;
            $alvoMetodo = null;
            $view = null;

            if (preg_match('/\[\s*([A-Za-z0-9_\\\\]+)::class\s*,\s*[\'"]([^\'"]+)[\'"]\s*\]/', $args, $a)) {
                $alvoClasse = PhpAnalyzer::resolve($a[1], $ns, $uses);
                $alvoMetodo = $a[2];
            } elseif (preg_match('/,\s*([A-Za-z0-9_\\\\]+)::class/', $args, $a)) {
                $alvoClasse = PhpAnalyzer::resolve($a[1], $ns, $uses);
                $alvoMetodo = in_array($verbo, ['resource', 'apiresource', 'singleton'], true) ? '(resource)' : '__invoke|render';
            } elseif (preg_match('/,\s*[\'"]([A-Za-z0-9_\\\\]+)@([A-Za-z0-9_]+)[\'"]/', $args, $a)) {
                $alvoClasse = PhpAnalyzer::resolve($a[1], $ns, $uses);
                $alvoMetodo = $a[2];
            } elseif ($verbo === 'view' && preg_match('/,\s*[\'"]([^\'"]+)[\'"]/', $args, $a)) {
                $view = $a[1];
            }

            $contexto = self::contexto($grupos, $abre);
            $nome = preg_match('/->\s*name\s*\(\s*[\'"]([^\'"]+)[\'"]/', $chain, $n) ? $n[1] : null;
            if ($nome !== null && $contexto['name'] !== '') {
                $nome = $contexto['name'] . $nome;
            }

            $middleware = preg_match('/->\s*middleware\s*\((.*?)\)/s', $chain, $mw)
                ? self::limpar($mw[1])
                : null;
            $middleware = trim(implode(', ', array_filter([$contexto['middleware'], $middleware])));

            $uriFinal = trim($contexto['prefix'] . '/' . ltrim($uri, '/'), '/');

            $rotas[] = [
                'arquivo' => $rel,
                'linha' => $linha,
                'verbo' => strtoupper($verbo),
                'uri' => '/' . $uriFinal,
                'classe' => $alvoClasse,
                'metodo' => $alvoMetodo,
                'view' => $view,
                'nome' => $nome,
                'middleware' => $middleware === '' ? null : $middleware,
            ];
        }

        return $rotas;
    }

    /** Blocos abertos por ->group(), com prefixo/middleware herdados. */
    private static function grupos(string $code): array
    {
        $grupos = [];
        if (! preg_match_all('/->\s*group\s*\(/', $code, $m, PREG_OFFSET_CAPTURE)) {
            return $grupos;
        }

        foreach ($m[0] as $hit) {
            $pos = $hit[1];
            $abre = strpos($code, '{', $pos);
            if ($abre === false) {
                continue;
            }
            $fecha = self::matchBrace($code, $abre);
            if ($fecha === null) {
                continue;
            }

            $inicio = max(
                (int) strrpos(substr($code, 0, $pos), ';'),
                (int) strrpos(substr($code, 0, $pos), '{')
            );
            $header = substr($code, $inicio, $pos - $inicio);

            $grupos[] = [
                'inicio' => $abre,
                'fim' => $fecha,
                'prefix' => preg_match('/prefix\s*\(\s*[\'"]([^\'"]+)[\'"]/', $header, $p) ? trim($p[1], '/') : '',
                'middleware' => preg_match('/middleware\s*\((.*?)\)/s', $header, $mw) ? self::limpar($mw[1]) : '',
                'name' => preg_match('/(?:->|::)\s*name\s*\(\s*[\'"]([^\'"]+)[\'"]/', $header, $n) ? $n[1] : '',
            ];
        }

        return $grupos;
    }

    private static function contexto(array $grupos, int $pos): array
    {
        $prefix = [];
        $middleware = [];
        $name = '';

        foreach ($grupos as $grupo) {
            if ($pos > $grupo['inicio'] && $pos < $grupo['fim']) {
                if ($grupo['prefix'] !== '') {
                    $prefix[] = $grupo['prefix'];
                }
                if ($grupo['middleware'] !== '') {
                    $middleware[] = $grupo['middleware'];
                }
                $name .= $grupo['name'];
            }
        }

        return [
            'prefix' => $prefix === [] ? '' : implode('/', $prefix),
            'middleware' => implode(', ', array_unique($middleware)),
            'name' => $name,
        ];
    }

    private static function limpar(string $txt): string
    {
        return trim(str_replace(['[', ']', "'", '"', "\n"], ['', '', '', '', ' '], $txt));
    }

    private static function matchParen(string $code, int $pos): ?int
    {
        return self::match($code, $pos, '(', ')');
    }

    private static function matchBrace(string $code, int $pos): ?int
    {
        return self::match($code, $pos, '{', '}');
    }

    private static function match(string $code, int $pos, string $abre, string $fecha): ?int
    {
        $depth = 0;
        $len = strlen($code);
        $aspas = null;

        for ($i = $pos; $i < $len; $i++) {
            $c = $code[$i];

            if ($aspas !== null) {
                if ($c === '\\') {
                    $i++;
                    continue;
                }
                if ($c === $aspas) {
                    $aspas = null;
                }
                continue;
            }

            if ($c === "'" || $c === '"') {
                $aspas = $c;
                continue;
            }
            if ($c === $abre) {
                $depth++;
                continue;
            }
            if ($c === $fecha) {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }
}

/**
 * Volt permite escrever um componente Livewire inteiro dentro do próprio
 * arquivo .blade.php, em um bloco `<?php ... ?>` no topo. Existem duas
 * sintaxes — funcional (`state()`, `mount()`, `$acao = function () {}`) e
 * baseada em classe (`new class extends Component { ... }`). Este analisador
 * lê esse bloco PHP com o mesmo tokenizer do PhpAnalyzer e devolve uma
 * estrutura de "classe" compatível com o restante do LaraMap, para que o
 * componente entre no mapa como qualquer outro Livewire.
 */
final class VoltAnalyzer
{
    private const GANCHOS_CLOSURE = [
        'mount', 'boot', 'booted', 'with', 'updated', 'updating',
        'hydrate', 'dehydrate', 'rendering', 'rendered', 'placeholder',
        'rules', 'messages', 'validationattributes',
    ];

    public static function isVolt(string $code): bool
    {
        if (preg_match('/^\s*<\?php/', $code) !== 1) {
            return false;
        }
        $head = substr($code, 0, 6000);

        return str_contains($head, 'Livewire\\Volt')
            || preg_match('/\bnew\s+class\b[^;{]{0,200}extends\s+Component/s', $head) === 1
            || preg_match('/\b(state|mount|computed|rules|placeholder|layout|title|uses|on)\s*\(/', $head) === 1;
    }

    public static function analyze(string $rel, string $code, string $view): ?array
    {
        $tokens = PhpAnalyzer::compact(\PhpToken::tokenize($code));
        if ($tokens === []) {
            return null;
        }

        $fqcn = 'Volt\\' . str_replace('.', '\\', $view);
        $ns = '';
        $uses = [];
        $corpo = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $tok = $tokens[$i];

            if ($tok->id === T_OPEN_TAG || $tok->id === T_CLOSE_TAG) {
                continue;
            }
            if ($tok->id === T_NAMESPACE) {
                [$ns, $i] = PhpAnalyzer::readName($tokens, $i + 1);
                continue;
            }
            if ($tok->id === T_USE) {
                if (($tokens[$i + 1]->id ?? 0) === T_FUNCTION) {
                    // use function Livewire\Volt\{state, mount, ...}; — não precisamos resolver os nomes
                    while ($i < $count && $tokens[$i]->text !== ';') {
                        $i++;
                    }
                    continue;
                }
                $i = PhpAnalyzer::readUses($tokens, $i + 1, $uses);
                continue;
            }

            $corpo[] = $tok;
        }

        if ($corpo === []) {
            return null;
        }

        foreach ($corpo as $idx => $tok) {
            if ($tok->id === T_NEW && (($corpo[$idx + 1]->id ?? 0) === T_CLASS)) {
                return self::parseClassBased($corpo, $idx + 1, $uses, $rel, $fqcn);
            }
        }

        return self::parseFuncional($corpo, $ns, $uses, $rel, $fqcn);
    }

    // ------------------------------------------------------------------
    // Volt baseado em classe: new class extends Component { ... }
    // ------------------------------------------------------------------

    private static function parseClassBased(array $tokens, int $classIndex, array $uses, string $rel, string $fqcn): array
    {
        // injeta um token de nome sintético (o próprio fqcn final) logo após
        // "class" pra reaproveitar PhpAnalyzer::readClass sem modificá-lo.
        $tokenNome = new \PhpToken(T_STRING, $fqcn, $tokens[$classIndex]->line);
        array_splice($tokens, $classIndex + 1, 0, [$tokenNome]);

        [$class] = PhpAnalyzer::readClass($tokens, $classIndex, '', $uses, [], $rel);
        if ($class === null) {
            return self::vazio($rel, $fqcn, $uses);
        }

        $class['volt'] = 'class-based';
        $class['categoria'] = 'livewire';
        $class['namespace'] = '';
        $class['uses'] = $uses;

        return $class;
    }

    // ------------------------------------------------------------------
    // Volt funcional: state(), mount(), $acao = function () {}, ...
    // ------------------------------------------------------------------

    private static function parseFuncional(array $tokens, string $ns, array $uses, string $rel, string $fqcn): array
    {
        $class = self::vazio($rel, $fqcn, $uses);
        $class['volt'] = 'functional';
        $class['categoria'] = 'livewire';
        $class['namespace'] = $ns;

        foreach (self::statements($tokens) as $stmt) {
            if ($stmt === []) {
                continue;
            }
            self::processarStatement($stmt, $ns, $uses, $fqcn, $class);
        }

        return $class;
    }

    private static function processarStatement(array $stmt, string $ns, array $uses, string $fqcn, array &$class): void
    {
        $primeiro = $stmt[0];

        // $nome = function/fn/computed(...)  — vira um "método" público do componente
        if ($primeiro->id === T_VARIABLE && (($stmt[1]->text ?? '') === '=')) {
            $nomeProp = ltrim($primeiro->text, '$');
            $valor = $stmt[2] ?? null;

            if ($valor !== null && $valor->id === T_FUNCTION) {
                $ajustado = self::anonimoComNome($stmt, 2);
                [$fn] = PhpAnalyzer::readFunction($ajustado, 2, $ns, $uses, ['public'], $class['propriedades'], [], $fqcn);
                $fn['nome'] = $nomeProp;
                $class['metodos'][] = $fn;

                return;
            }
            if ($valor !== null && $valor->id === T_FN) {
                $class['metodos'][] = self::parseArrow($stmt, 2, $ns, $uses, $class['propriedades'], $fqcn, $nomeProp);

                return;
            }
            if ($valor !== null && $valor->id === T_STRING && strtolower($valor->text) === 'computed' && (($stmt[3]->text ?? '') === '(')) {
                $m = self::localizarClosure($stmt, 4, $ns, $uses, $class['propriedades'], $fqcn, $nomeProp);
                if ($m !== null) {
                    $m['atributos'][] = ['nome' => 'Computed', 'arg' => null, 'raw' => ''];
                    $class['metodos'][] = $m;
                }

                return;
            }

            // atribuição comum: trata como estado local do componente
            $class['propriedades'][$nomeProp] = ['tipo' => null, 'modificadores' => ['public'], 'linha' => $primeiro->line];

            return;
        }

        // chamadas de nível superior: state(), mount(), on(), rules(), layout(), title(), uses()
        if ($primeiro->id === T_STRING && (($stmt[1]->text ?? '') === '(')) {
            self::processarChamada(strtolower($primeiro->text), $stmt, $ns, $uses, $fqcn, $class);
        }
    }

    private static function processarChamada(string $nome, array $stmt, string $ns, array $uses, string $fqcn, array &$class): void
    {
        $linha = $stmt[0]->line;

        if ($nome === 'state') {
            foreach (self::chavesDoArray($stmt) as $chave) {
                $class['propriedades'][$chave] ??= ['tipo' => null, 'modificadores' => ['public'], 'linha' => $linha];
            }

            return;
        }

        if ($nome === 'uses') {
            foreach (self::classesDoArray($stmt, $ns, $uses) as $traitFqcn) {
                $class['traits'][] = $traitFqcn;
            }

            return;
        }

        if ($nome === 'layout') {
            $arg = PhpAnalyzer::firstString($stmt, 1);
            if ($arg !== null) {
                $class['atributos'][] = ['nome' => 'Layout', 'arg' => $arg, 'raw' => ''];
            }

            return;
        }

        if ($nome === 'title') {
            $arg = PhpAnalyzer::firstString($stmt, 1);
            if ($arg !== null) {
                $class['atributos'][] = ['nome' => 'Title', 'arg' => $arg, 'raw' => ''];
            }

            return;
        }

        if ($nome === 'on') {
            foreach (self::paresDoArray($stmt) as [$evento, $indiceClosure]) {
                $slug = 'on_' . preg_replace('/[^a-z0-9]+/i', '_', $evento);
                $m = self::localizarClosure($stmt, $indiceClosure, $ns, $uses, $class['propriedades'], $fqcn, (string) $slug);
                if ($m !== null) {
                    $m['atributos'][] = ['nome' => 'On', 'arg' => $evento, 'raw' => ''];
                    $class['metodos'][] = $m;
                }
            }

            return;
        }

        if (in_array($nome, self::GANCHOS_CLOSURE, true)) {
            $m = self::localizarClosure($stmt, 1, $ns, $uses, $class['propriedades'], $fqcn, $nome);
            if ($m !== null) {
                $class['metodos'][] = $m;
            }
        }
    }

    /** Acha o primeiro `function`/`fn` a partir de $desdeIdx e o transforma num método chamado $nome. */
    private static function localizarClosure(array $stmt, int $desdeIdx, string $ns, array $uses, array $props, string $owner, string $nome): ?array
    {
        $count = count($stmt);
        for ($i = $desdeIdx; $i < $count; $i++) {
            $id = $stmt[$i]->id ?? 0;
            if ($id === T_FUNCTION) {
                $ajustado = self::anonimoComNome($stmt, $i);
                [$fn] = PhpAnalyzer::readFunction($ajustado, $i, $ns, $uses, ['public'], $props, [], $owner);
                $fn['nome'] = $nome;

                return $fn;
            }
            if ($id === T_FN) {
                return self::parseArrow($stmt, $i, $ns, $uses, $props, $owner, $nome);
            }
        }

        return null;
    }

    /** Analisa `fn (...) => expressao`, reaproveitando PhpAnalyzer::analyzeBody na expressão. */
    private static function parseArrow(array $s, int $fnIndex, string $ns, array $uses, array $props, string $owner, string $nome): array
    {
        $count = count($s);
        $linha = $s[$fnIndex]->line;
        $i = $fnIndex + 1;
        $params = [];
        $paramTypes = [];

        if (($s[$i]->text ?? '') === '(') {
            $depth = 0;
            $typeBuf = '';
            for (; $i < $count; $i++) {
                $tok = $s[$i];
                if ($tok->text === '(') {
                    $depth++;
                    continue;
                }
                if ($tok->text === ')') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                    continue;
                }
                if ($depth !== 1) {
                    continue;
                }
                if ($tok->text === ',') {
                    $typeBuf = '';
                    continue;
                }
                if ($tok->id === T_VARIABLE) {
                    $tipo = $typeBuf !== '' ? PhpAnalyzer::resolve($typeBuf, $ns, $uses) : null;
                    $params[] = ['nome' => $tok->text, 'tipo' => $tipo, 'nullable' => false];
                    if ($tipo !== null) {
                        $paramTypes[$tok->text] = $tipo;
                    }
                    $typeBuf = '';
                    continue;
                }
                if (PhpAnalyzer::isName($tok) || $tok->id === T_ARRAY) {
                    if ($typeBuf === '') {
                        $typeBuf = $tok->text;
                    }
                }
            }
        }
        $i++;
        if (($s[$i]->text ?? '') === '=>') {
            $i++;
        }

        $corpo = array_slice($s, $i);
        $refs = PhpAnalyzer::analyzeBody($corpo, $ns, $uses, $props, $paramTypes, $owner);

        return [
            'nome' => $nome,
            'linha' => $linha,
            'modificadores' => ['public'],
            'parametros' => $params,
            'retorno' => null,
            'atributos' => [],
            'refs' => $refs,
            'abstrato' => false,
        ];
    }

    // ------------------------------------------------------------------
    // Utilidades de tokens
    // ------------------------------------------------------------------

    /** Quebra a sequência de tokens em statements de nível 0 (separados por ';'). */
    private static function statements(array $tokens): array
    {
        $count = count($tokens);
        $depth = 0;
        $start = 0;
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $tok = $tokens[$i];
            $texto = $tok->text;

            if ($texto === '(' || $texto === '[' || $texto === '{' || $tok->id === T_CURLY_OPEN || $tok->id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
                continue;
            }
            if ($texto === ')' || $texto === ']' || $texto === '}') {
                $depth--;
                continue;
            }
            if ($depth === 0 && $texto === ';') {
                if ($i > $start) {
                    $out[] = array_slice($tokens, $start, $i - $start);
                }
                $start = $i + 1;
            }
        }
        if ($start < $count) {
            $resto = array_slice($tokens, $start);
            if ($resto !== []) {
                $out[] = $resto;
            }
        }

        return $out;
    }

    /** Chaves de string de um array literal (`['a' => 1, 'b']`) em qualquer chamada do statement. */
    private static function chavesDoArray(array $stmt): array
    {
        $inicio = self::indiceDoColchete($stmt);
        if ($inicio === null) {
            return [];
        }

        $count = count($stmt);
        $depth = 0;
        $chaves = [];
        for ($i = $inicio; $i < $count; $i++) {
            $tok = $stmt[$i];
            if ($tok->text === '[') {
                $depth++;
                continue;
            }
            if ($tok->text === ']') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
                continue;
            }
            if ($depth === 1 && $tok->id === T_CONSTANT_ENCAPSED_STRING) {
                $prox = $stmt[$i + 1]->text ?? '';
                if ($prox === '=>' || $prox === ',' || $prox === ']') {
                    $chaves[] = trim($tok->text, "'\"");
                }
            }
        }

        return array_values(array_unique($chaves));
    }

    /** Pares `'evento' => function/fn` de um array literal (usado por on([...])). */
    private static function paresDoArray(array $stmt): array
    {
        $inicio = self::indiceDoColchete($stmt);
        if ($inicio === null) {
            return [];
        }

        $count = count($stmt);
        $depth = 0;
        $pares = [];
        for ($i = $inicio; $i < $count; $i++) {
            $tok = $stmt[$i];
            if ($tok->text === '[') {
                $depth++;
                continue;
            }
            if ($tok->text === ']') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
                continue;
            }
            if ($depth === 1 && $tok->id === T_CONSTANT_ENCAPSED_STRING && (($stmt[$i + 1]->text ?? '') === '=>')) {
                $alvoIdx = $i + 2;
                if (in_array($stmt[$alvoIdx]->id ?? 0, [T_FUNCTION, T_FN], true)) {
                    $pares[] = [trim($tok->text, "'\""), $alvoIdx];
                }
            }
        }

        return $pares;
    }

    /** Classes citadas como `Nome::class` dentro do statement (usado por uses([...])). */
    private static function classesDoArray(array $stmt, string $ns, array $uses): array
    {
        $out = [];
        $count = count($stmt);
        for ($i = 0; $i < $count; $i++) {
            if (PhpAnalyzer::isName($stmt[$i])
                && (($stmt[$i + 1]->text ?? '') === '::')
                && strtolower($stmt[$i + 2]->text ?? '') === 'class'
            ) {
                [$fq] = PhpAnalyzer::readName($stmt, $i);
                $out[] = PhpAnalyzer::resolve($fq, $ns, $uses);
            }
        }

        return $out;
    }

    /**
     * PhpAnalyzer::readFunction() espera um nome logo após 'function' (é
     * assim que métodos normais funcionam). Uma closure anônima não tem
     * nome — o token seguinte já é '('. Sem esse ajuste, o parser confundiria
     * o primeiro parâmetro com o nome da função e perderia os parâmetros.
     */
    private static function anonimoComNome(array $tokens, int $funcIndex): array
    {
        $proximo = $tokens[$funcIndex + 1] ?? null;
        if ($proximo !== null && $proximo->id === T_STRING) {
            return $tokens; // já nomeada — não deveria ocorrer aqui, mas por segurança
        }

        $sintetico = new \PhpToken(T_STRING, '__volt_closure__', $tokens[$funcIndex]->line);
        array_splice($tokens, $funcIndex + 1, 0, [$sintetico]);

        return $tokens;
    }

    private static function indiceDoColchete(array $stmt): ?int
    {
        foreach ($stmt as $idx => $tok) {
            if ($tok->text === '[') {
                return $idx;
            }
        }

        return null;
    }

    private static function vazio(string $rel, string $fqcn, array $uses): array
    {
        return [
            'nome' => $fqcn,
            'fqcn' => $fqcn,
            'kind' => 'class',
            'linha' => 1,
            'arquivo' => $rel,
            'extends' => ['Livewire\\Volt\\Component'],
            'implements' => [],
            'traits' => [],
            'atributos' => [],
            'propriedades' => [],
            'constantes' => [],
            'metodos' => [],
            'namespace' => '',
            'uses' => $uses,
            'volt' => 'functional',
            'categoria' => 'livewire',
        ];
    }
}

/**
 * Lê um arquivo de migration e extrai, de `Schema::create`/`Schema::table`,
 * as tabelas e colunas declaradas via Blueprint — incluindo chaves
 * estrangeiras (`foreignId()->constrained()`, `foreign()->references()->on()`).
 * Devolve uma lista de OPERAÇÕES (create/alter/drop/rename); quem acumula o
 * estado final do banco, aplicando-as em ordem cronológica, é o Indexer.
 */
final class MigrationAnalyzer
{
    /** Métodos de Blueprint que não criam coluna nova — são metadado ou restrição sobre coluna existente. */
    private const NAO_CRIA_COLUNA = [
        'engine', 'charset', 'collation', 'comment', 'temporary',
        'dropforeign', 'dropunique', 'dropindex', 'dropprimary',
        'dropsoftdeletes', 'dropsoftdeletestz', 'droptimestamps', 'droptimestampstz',
        'dropmorphs', 'droprememberttoken', 'droprememberToken', 'dropcolumn', 'dropcolumns',
        'unique', 'index', 'primary', 'fulltext', 'spatialindex', 'foreign', 'rawindex',
    ];

    /** Métodos que criam mais de uma coluna de uma vez. */
    private const MULTI_COLUNA = ['timestamps', 'timestampstz', 'nullabletimestamps', 'softdeletes', 'softdeletestz', 'remembertoken', 'morphs', 'nullablemorphs', 'uuidmorphs', 'ulidmorphs'];

    public static function analyze(string $rel, string $code): array
    {
        $todos = PhpAnalyzer::compact(\PhpToken::tokenize($code));
        $tokens = self::corpoDoMetodoUp($todos);
        $count = count($tokens);
        $operacoes = [];

        for ($i = 0; $i < $count; $i++) {
            $tok = $tokens[$i];
            if (strtolower($tok->text) !== 'schema' || ($tokens[$i + 1]->text ?? '') !== '::') {
                continue;
            }

            $metodo = strtolower($tokens[$i + 2]->text ?? '');
            if (! in_array($metodo, ['create', 'table', 'dropifexists', 'drop', 'rename'], true)) {
                continue;
            }

            $parenIdx = $i + 3;
            if (($tokens[$parenIdx]->text ?? '') !== '(') {
                continue;
            }
            $fimParen = self::matchParen($tokens, $parenIdx);
            $linha = $tok->line;
            $args = array_slice($tokens, $parenIdx, $fimParen - $parenIdx + 1);

            if ($metodo === 'dropifexists' || $metodo === 'drop') {
                $nomes = self::stringsDosArgs($args);
                if (isset($nomes[0])) {
                    $operacoes[] = ['tipo' => 'drop', 'tabela' => $nomes[0], 'arquivo' => $rel, 'linha' => $linha];
                }
                $i = $fimParen;
                continue;
            }

            if ($metodo === 'rename') {
                $nomes = self::stringsDosArgs($args);
                if (isset($nomes[0], $nomes[1])) {
                    $operacoes[] = ['tipo' => 'rename', 'de' => $nomes[0], 'para' => $nomes[1], 'arquivo' => $rel, 'linha' => $linha];
                }
                $i = $fimParen;
                continue;
            }

            // create / table
            $nomes = self::stringsDosArgs($args);
            $tabela = $nomes[0] ?? null;
            $fechamento = self::corpoDoClosure($tokens, $parenIdx, $fimParen);
            $i = $fimParen;

            if ($tabela === null || $fechamento === null) {
                continue;
            }
            [$nomeParam, $corpo] = $fechamento;

            $acc = ['colunas' => [], 'remover' => []];
            foreach (self::statements($corpo) as $stmt) {
                if ($stmt === [] || ($stmt[0]->text ?? '') !== $nomeParam) {
                    continue;
                }
                self::processarColuna(self::cadeia($stmt), $acc, $stmt[0]->line);
            }

            $operacoes[] = [
                'tipo' => $metodo === 'create' ? 'create' : 'alter',
                'tabela' => $tabela,
                'colunas' => $acc['colunas'],
                'remover' => $acc['remover'],
                'arquivo' => $rel,
                'linha' => $linha,
            ];
        }

        return $operacoes;
    }

    // ------------------------------------------------------------------

    private static function processarColuna(array $chamadas, array &$acc, int $linha): void
    {
        if ($chamadas === []) {
            return;
        }
        $primeira = $chamadas[0];
        $metodo = $primeira['metodo'];

        if ($metodo === 'dropcolumn' || $metodo === 'dropcolumns') {
            foreach (self::stringsDosArgs($primeira['args']) as $col) {
                $acc['remover'][] = $col;
            }

            return;
        }

        if ($metodo === 'primary') {
            foreach (self::stringsDosArgs($primeira['args']) as $col) {
                if (isset($acc['colunas'][$col])) {
                    $acc['colunas'][$col]['pk'] = true;
                }
            }

            return;
        }

        if ($metodo === 'unique' || $metodo === 'index') {
            if ($metodo === 'unique') {
                foreach (self::stringsDosArgs($primeira['args']) as $col) {
                    if (isset($acc['colunas'][$col])) {
                        $acc['colunas'][$col]['unique'] = true;
                    }
                }
            }

            return;
        }

        if ($metodo === 'foreign') {
            $col = self::stringsDosArgs($primeira['args'])[0] ?? null;
            if ($col === null) {
                return;
            }
            [$fkTabela, $fkColuna] = self::fkDaCadeia($chamadas, null, null);
            $fk = $fkTabela !== null ? ['tabela' => $fkTabela, 'coluna' => $fkColuna ?? 'id'] : null;
            if (isset($acc['colunas'][$col])) {
                $acc['colunas'][$col]['fk'] = $fk;
            } else {
                $acc['colunas'][$col] = self::colunaBase($col, 'bigint', $linha);
                $acc['colunas'][$col]['fk'] = $fk;
            }

            return;
        }

        if (in_array($metodo, self::NAO_CRIA_COLUNA, true)) {
            return;
        }

        if (in_array($metodo, self::MULTI_COLUNA, true)) {
            foreach (self::colunasMultiplas($metodo, $primeira['args']) as $col => $tipo) {
                $acc['colunas'][$col] = self::colunaBase($col, $tipo, $linha, nullable: true);
            }

            return;
        }

        if ($metodo === 'id') {
            $col = self::stringsDosArgs($primeira['args'])[0] ?? 'id';
            $c = self::colunaBase($col, 'bigint', $linha);
            $c['pk'] = true;
            $acc['colunas'][$col] = $c;

            return;
        }

        // caso geral: metodo('nome', ...) cria/edita uma coluna
        $args = self::stringsDosArgs($primeira['args']);
        $nomeCol = $args[0] ?? null;
        if ($nomeCol === null) {
            return;
        }

        $coluna = $acc['colunas'][$nomeCol] ?? self::colunaBase($nomeCol, $metodo, $linha);
        $coluna['tipo'] = $metodo;
        $coluna['linha'] = $linha;

        $ehForeignId = in_array($metodo, ['foreignid', 'foreignuuid', 'foreignulid'], true);
        [$fkTabela, $fkColuna] = self::fkDaCadeia($chamadas, $ehForeignId ? self::pluralizar((string) preg_replace('/_id$/', '', $nomeCol)) : null, null);

        foreach (array_slice($chamadas, 1) as $mod) {
            switch ($mod['metodo']) {
                case 'nullable':
                    $coluna['nullable'] = true;
                    break;
                case 'unique':
                    $coluna['unique'] = true;
                    break;
                case 'primary':
                    $coluna['pk'] = true;
                    break;
                case 'default':
                    $valores = self::stringsDosArgs($mod['args']);
                    $coluna['default'] = $valores[0] ?? null;
                    break;
            }
        }

        if ($fkTabela !== null) {
            $coluna['fk'] = ['tabela' => $fkTabela, 'coluna' => $fkColuna ?? 'id'];
        }

        $acc['colunas'][$nomeCol] = $coluna;
    }

    /** Varre os modificadores encadeados (constrained/references/on) e devolve [tabela, coluna] da FK, se houver. */
    private static function fkDaCadeia(array $chamadas, ?string $tabelaPadrao, ?string $colunaPadrao): array
    {
        $tabela = null;
        $coluna = null;
        $temFk = false;

        foreach (array_slice($chamadas, 1) as $mod) {
            if ($mod['metodo'] === 'constrained') {
                $args = self::stringsDosArgs($mod['args']);
                $tabela = $args[0] ?? $tabelaPadrao;
                $coluna = $args[1] ?? 'id';
                $temFk = true;
            } elseif ($mod['metodo'] === 'references') {
                $args = self::stringsDosArgs($mod['args']);
                $coluna = $args[0] ?? 'id';
                $temFk = true;
            } elseif ($mod['metodo'] === 'on') {
                $args = self::stringsDosArgs($mod['args']);
                $tabela = $args[0] ?? $tabela;
                $temFk = true;
            }
        }

        if (! $temFk) {
            return [null, null];
        }

        return [$tabela ?? $tabelaPadrao, $coluna ?? $colunaPadrao ?? 'id'];
    }

    private static function colunasMultiplas(string $metodo, array $argsComParens): array
    {
        $args = self::stringsDosArgs($argsComParens);

        return match ($metodo) {
            'timestamps', 'timestampstz', 'nullabletimestamps' => ['created_at' => 'timestamp', 'updated_at' => 'timestamp'],
            'softdeletes', 'softdeletestz' => [($args[0] ?? 'deleted_at') => 'timestamp'],
            'remembertoken' => ['remember_token' => 'string'],
            'morphs', 'nullablemorphs', 'uuidmorphs', 'ulidmorphs' => [
                (($args[0] ?? 'morphable') . '_type') => 'string',
                (($args[0] ?? 'morphable') . '_id') => str_starts_with($metodo, 'uuid') ? 'uuid' : (str_starts_with($metodo, 'ulid') ? 'ulid' : 'bigint'),
            ],
            default => [],
        };
    }

    private static function colunaBase(string $nome, string $tipo, int $linha, bool $nullable = false): array
    {
        return [
            'nome' => $nome,
            'tipo' => $tipo,
            'nullable' => $nullable,
            'unique' => false,
            'pk' => false,
            'fk' => null,
            'default' => null,
            'linha' => $linha,
        ];
    }

    // ------------------------------------------------------------------
    // Utilidades de tokens (independentes do VoltAnalyzer — duplicadas de propósito
    // para não acoplar os dois analisadores)
    // ------------------------------------------------------------------

    /**
     * Restringe a análise ao corpo do método up() — down() faz o caminho
     * inverso (dropIfExists, etc.) e não deve contar como "estado atual".
     * Sem isso, create() em up() e dropIfExists() em down() do MESMO
     * arquivo se cancelariam ao serem lidos em sequência.
     */
    private static function corpoDoMetodoUp(array $tokens): array
    {
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (($tokens[$i]->id ?? 0) !== T_FUNCTION) {
                continue;
            }
            $j = $i + 1;
            if (($tokens[$j]->text ?? '') === '&') {
                $j++;
            }
            if (strcasecmp($tokens[$j]->text ?? '', 'up') !== 0) {
                continue;
            }

            $pIdx = $j + 1;
            if (($tokens[$pIdx]->text ?? '') !== '(') {
                continue;
            }
            $pFim = self::matchParen($tokens, $pIdx);

            $chaveIdx = $pFim + 1;
            while ($chaveIdx < $count && ($tokens[$chaveIdx]->text ?? '') !== '{') {
                $chaveIdx++;
            }
            if (($tokens[$chaveIdx]->text ?? '') !== '{') {
                continue;
            }
            $chaveFim = PhpAnalyzer::matchBrace($tokens, $chaveIdx);

            return array_slice($tokens, $chaveIdx + 1, max(0, $chaveFim - $chaveIdx - 1));
        }

        return [];
    }

    /** Acha o `function (Tipo $nome) { ... }` dentro do intervalo de argumentos e devolve [nomeDoParametro, tokensDoCorpo]. */
    private static function corpoDoClosure(array $tokens, int $inicio, int $fim): ?array
    {
        for ($i = $inicio; $i < $fim; $i++) {
            if (($tokens[$i]->id ?? 0) !== T_FUNCTION) {
                continue;
            }

            $pIdx = $i + 1;
            if (($tokens[$pIdx]->text ?? '') !== '(') {
                return null;
            }
            $pFim = self::matchParen($tokens, $pIdx);

            $nomeParam = null;
            for ($j = $pIdx; $j <= $pFim; $j++) {
                if (($tokens[$j]->id ?? 0) === T_VARIABLE) {
                    $nomeParam = $tokens[$j]->text;
                    break;
                }
            }
            if ($nomeParam === null) {
                return null;
            }

            $chaveIdx = $pFim + 1;
            while ($chaveIdx <= $fim && ($tokens[$chaveIdx]->text ?? '') !== '{') {
                $chaveIdx++;
            }
            if (($tokens[$chaveIdx]->text ?? '') !== '{') {
                return null;
            }
            $chaveFim = PhpAnalyzer::matchBrace($tokens, $chaveIdx);
            $corpo = array_slice($tokens, $chaveIdx + 1, max(0, $chaveFim - $chaveIdx - 1));

            return [$nomeParam, $corpo];
        }

        return null;
    }

    /** Quebra tokens em statements de nível 0 (separados por ';'). */
    private static function statements(array $tokens): array
    {
        $count = count($tokens);
        $depth = 0;
        $start = 0;
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $texto = $tokens[$i]->text;
            if ($texto === '(' || $texto === '[' || $texto === '{') {
                $depth++;
                continue;
            }
            if ($texto === ')' || $texto === ']' || $texto === '}') {
                $depth--;
                continue;
            }
            if ($depth === 0 && $texto === ';') {
                if ($i > $start) {
                    $out[] = array_slice($tokens, $start, $i - $start);
                }
                $start = $i + 1;
            }
        }
        if ($start < $count) {
            $resto = array_slice($tokens, $start);
            if ($resto !== []) {
                $out[] = $resto;
            }
        }

        return $out;
    }

    /** Quebra `$table->metodo(args)->metodo2(args2)...` numa lista ordenada de chamadas. */
    private static function cadeia(array $stmt): array
    {
        $count = count($stmt);
        $chamadas = [];
        $i = ($stmt[0]->id ?? 0) === T_VARIABLE ? 1 : 0;

        while ($i < $count) {
            if (($stmt[$i]->text ?? '') !== '->') {
                $i++;
                continue;
            }
            $i++;
            $nome = $stmt[$i]->text ?? '';
            $i++;
            if (($stmt[$i]->text ?? '') !== '(') {
                continue;
            }
            $parenIdx = $i;
            $fim = self::matchParen($stmt, $parenIdx);
            $chamadas[] = ['metodo' => strtolower($nome), 'args' => array_slice($stmt, $parenIdx, $fim - $parenIdx + 1)];
            $i = $fim + 1;
        }

        return $chamadas;
    }

    /** Valores de string literal entre os argumentos top-level de uma chamada (array já inclui os parênteses). */
    private static function stringsDosArgs(array $argsComParens): array
    {
        $brutos = PhpAnalyzer::argsSource($argsComParens, 0, 0);
        $out = [];
        foreach ($brutos as $b) {
            $b = trim($b);
            $primeiro = $b[0] ?? '';
            $out[] = ($primeiro === "'" || $primeiro === '"') ? trim($b, "'\"") : $b;
        }

        return $out;
    }

    private static function matchParen(array $tokens, int $abre): int
    {
        $depth = 0;
        $count = count($tokens);
        for ($i = $abre; $i < $count; $i++) {
            if ($tokens[$i]->text === '(') {
                $depth++;
            } elseif ($tokens[$i]->text === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return $count - 1;
    }

    private static function pluralizar(string $s): string
    {
        $s = trim($s, '_');
        if (preg_match('/[^aeiouAEIOU]y$/', $s) === 1) {
            return substr($s, 0, -1) . 'ies';
        }
        if (preg_match('/(s|x|z|ch|sh)$/i', $s) === 1) {
            return $s . 'es';
        }

        return $s . 's';
    }
}

/**
 * Cruza tudo que os analisadores extraíram: resolve quem chama quem,
 * qual view pertence a qual componente e onde cada model é usado.
 */
/**
 * Inventário de textos do sistema: tudo que o usuário final pode ler
 * (texto de views, atributos como placeholder/title, mensagens em PHP,
 * traduções, strings de JS). Frases com "tag de personalização" no meio
 * ({{ $nome }}, {nome}, {{nome}}, $nome interpolado...) são mantidas inteiras.
 */
final class Textos
{
    /** Tags de personalização: {{ x }}, {!! x !!}, ${x}, {$x}, {nome}, %s, %nome%, :nome, [nome]. */
    public const PLACEHOLDER_RE = '/\{\{.*?\}\}|\{!!.*?!!\}|\$\{[^}]*\}|\{\$[^}]*\}|\{[A-Za-z_][\w.\-]*\}|%(?:\d+\$)?[sdfu]\b|%[A-Za-z_]+%|:[a-z_]{2,}\b|\[\[?[a-z_]{2,}\]?\]/s';

    private const TECNICOS_MAIUSCULOS = [
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'NULL', 'TRUE', 'FALSE', 'JSON', 'CSV', 'XML',
        'HTML', 'UTF', 'ASC', 'DESC', 'AND', 'OR', 'NOT', 'LIKE', 'IN', 'ID', 'URL', 'URI', 'API', 'SQL', 'MIME',
        'HTTP', 'HTTPS', 'CSRF', 'UUID',
    ];

    private const CSS_PREFIXOS = '/^(?:btn|col|row|d|p[xytblrs]?|m[xytblrs]?|text|bg|border|flex|grid|w|h|rounded|shadow|font|gap|items|justify|space|min|max|fixed|absolute|relative|hidden|block|inline|container|card|badge|alert|table|form|input|nav|navbar|modal|dropdown|fa|fas|far|fab|bi|icon|hover|focus|dark|sm|md|lg|xl|z|top|bottom|left|right|overflow|cursor|transition|opacity|truncate|uppercase|lowercase|capitalize|underline|sr|ring|divide|fill|stroke|object|aspect|order|self|place|content|list|float|clear|visible|invisible|static|sticky)(?:-|$)/';

    private const TAGS_HTML = 'p|br|div|span|a|b|strong|i|em|u|ul|ol|li|h[1-6]|table|thead|tbody|tfoot|tr|td|th|img|hr|center|font|small|html|body|head|style|title|section|header|footer|button|label|input|form|blockquote|pre|code|mark|sup|sub|s|del|ins';

    /** Garante UTF-8 sem depender de mbstring (arquivos em Windows-1252 existem). */
    public static function utf8(string $s): string
    {
        if ($s === '' || preg_match('//u', $s) === 1) {
            return $s;
        }
        if (function_exists('iconv')) {
            $r = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
            if ($r !== false) {
                return $r;
            }
        }

        return (string) preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '?', $s);
    }

    public static function normalizar(string $s): string
    {
        $s = self::utf8($s);
        $r = preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', ' ', $s);

        return trim($r ?? $s);
    }

    public static function semPlaceholders(string $s): string
    {
        return trim((string) preg_replace(self::PLACEHOLDER_RE, ' ', $s));
    }

    public static function temLetra(string $s): bool
    {
        return preg_match('/\p{L}/u', $s) === 1;
    }

    public static function temHtml(string $s): bool
    {
        return preg_match('~</?(?:' . self::TAGS_HTML . ')\b[^<>]*>~i', $s) === 1;
    }

    public static function minusculas(string $s): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($s, 'UTF-8');
        }

        return strtr(strtolower($s), [
            'Á' => 'á', 'À' => 'à', 'Â' => 'â', 'Ã' => 'ã', 'Ä' => 'ä', 'É' => 'é', 'È' => 'è', 'Ê' => 'ê',
            'Í' => 'í', 'Ó' => 'ó', 'Ô' => 'ô', 'Õ' => 'õ', 'Ú' => 'ú', 'Ü' => 'ü', 'Ç' => 'ç', 'Ñ' => 'ñ',
        ]);
    }

    public static function decodificarEcos(string $s, array $ecos): string
    {
        if ($ecos === [] || ! str_contains($s, "\x01")) {
            return $s;
        }

        return (string) preg_replace_callback('/\x01(\d+)\x02/', fn (array $m): string => $ecos[(int) $m[1]] ?? '', $s);
    }

    /** Corta sem quebrar caracteres multibyte. */
    public static function cortar(string $s, int $max): string
    {
        if (strlen($s) <= $max) {
            return $s;
        }
        if (preg_match('/^.{0,' . $max . '}/us', $s, $m) === 1) {
            return $m[0] . '…';
        }

        return substr($s, 0, $max) . '…';
    }

    /**
     * Decide se uma string parece texto de interface (e não chave, classe CSS,
     * caminho, SQL, formato de data...). $forcado = contexto que já indica
     * texto (placeholder="", __(), 'message' => ...). $todas = sem filtro.
     */
    public static function pareceTexto(string $s, bool $forcado = false, bool $todas = false): bool
    {
        $s = trim($s);
        if ($s === '' || strlen($s) > 8000) {
            return false;
        }

        $sem = self::semPlaceholders($s);
        if (preg_match('/\p{L}/u', $sem) !== 1) {
            return false;
        }
        if ($todas) {
            return true;
        }

        // placeholder vira 'x' (e não espaço) para não "criar" palavras: regras.{$i}.campo, in:a,b
        $flat = trim((string) preg_replace(self::PLACEHOLDER_RE, 'x', $s));

        // código, endereços, caminhos, chaves — nunca são texto de interface
        if (preg_match('/->|::|=>|<\?|\(\)|;\s*$|\bfunction\s*\(|\breturn\s|\bnew\s+[A-Z]/', $sem) === 1) {
            return false;
        }
        if (preg_match('~^(?:https?:)?//\S+$|^www\.\S+$|^\S+@\S+\.\S+$~i', $s) === 1) {
            return false;
        }
        if (preg_match('~^[\w.\-]+\.(?:php|js|css|png|jpe?g|svg|gif|webp|pdf|xlsx?|csv|json|html?|txt|md|zip|ico)$~i', $s) === 1) {
            return false;
        }
        if (preg_match('~^[a-z0-9_.\-]+(?:/[a-z0-9_.\-]*)+$~', $flat) === 1) {
            return false;
        }
        if (strpos($s, '\\') !== false && preg_match('/\s/', $s) !== 1) {
            return false;
        }
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $s) === 1) {
            return false;
        }
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $s) === 1) {
            return false;
        }
        if (preg_match('/^\s*(?:select|insert|update|delete|create|alter|drop)\b.+\b(?:from|into|set|table)\b/is', $sem) === 1) {
            return false;
        }
        if (preg_match('/^[a-z0-9_]+(?:\.[a-z0-9_\-*]+)+$/', $flat) === 1) {
            return false; // chaves de tradução/config: auth.failed, ciclo.regras, regras.{$i}.campo
        }

        if ($forcado) {
            return true;
        }

        if (preg_match('/^[\[{].*[\]}]$/s', $s) === 1 && preg_match('/["\']\s*:/', $s) === 1) {
            return false; // JSON / array literal
        }
        if (preg_match('~^/.+/[a-z]*$~s', $s) === 1 && preg_match('~\\\\[dwsbDWSB]|\(\?|\[\^~', $s) === 1) {
            return false; // regex
        }

        if (preg_match('/\s/u', $flat) !== 1) {
            return self::palavraSolta($flat);
        }

        // formato de data: d/m/Y H:i
        if (preg_match('/^[dDjlNSwzWFmMntLoYyaABgGhHisuveIOPTpcrU\s\/\-:.,]+$/', $s) === 1
            && preg_match('/[\/\-:.]/', $s) === 1
            && preg_match('/\p{L}{4,}/u', $s) !== 1) {
            return false;
        }

        $tokens = preg_split('/\s+/u', $sem, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ascii = true;
        $marcadores = 0;
        $css = 0;
        foreach ($tokens as $t) {
            if (preg_match('/^[a-z0-9_\-:\/\[\]\.#%!@=,|()*+]+$/', $t) !== 1) {
                $ascii = false;
                break;
            }
            if (preg_match('/[\-:\/\[\]_=|]/', $t) === 1) {
                $marcadores++;
            }
            if (preg_match(self::CSS_PREFIXOS, $t) === 1) {
                $css++;
            }
        }
        if ($ascii) {
            $n = count($tokens);
            if ($marcadores * 2 > $n) {
                return false; // lista de classes CSS / opções técnicas
            }
            if ($n >= 2 && $css * 2 >= $n) {
                return false;
            }
        }

        return preg_match('/\p{L}{2,}/u', $sem) === 1;
    }

    private static function palavraSolta(string $w): bool
    {
        $w = trim($w, " \t.:!?…;,()\"'«»“”‘’-–—");
        if ($w === '' || preg_match('/\p{L}/u', $w) !== 1) {
            return false;
        }
        if (preg_match('/^\p{Lu}\/\p{Lu}$/u', $w) === 1) {
            return true; // N/A, S/N
        }
        if (preg_match('/[_\\\\|=@{}$#<>]/', $w) === 1) {
            return false;
        }
        if (preg_match('/^[a-z][a-z0-9\-:.,*\/]*$/', $w) === 1) {
            return false; // minúscula ASCII: chave/valor técnico
        }
        if (preg_match('/^[a-z]+(?:[A-Z][a-z0-9]*)+$/', $w) === 1) {
            return false; // camelCase
        }
        if (preg_match('/^(?:\p{Lu}\p{Ll}+){2,}\d*$/u', $w) === 1) {
            return false; // PascalCase: nome de classe
        }
        if (preg_match('/^[\p{Lu}0-9]+$/u', $w) === 1) {
            return strlen($w) >= 2 && ! in_array($w, self::TECNICOS_MAIUSCULOS, true);
        }
        if (preg_match('/^\p{Lu}[\p{L}\p{M}\'’\-.\/]*$/u', $w) === 1) {
            return true; // Salvar, Pré-inscrição, Sim/Não
        }
        if (preg_match('/^[\p{L}\p{M}\-]+$/u', $w) === 1 && preg_match('/[^\x00-\x7F]/', $w) === 1) {
            return true; // ação, inscrição
        }

        return false;
    }
}

final class ExtratorHtml
{
    private const INLINE = ['b', 'strong', 'i', 'em', 'u', 'small', 'mark', 'code', 'sub', 'sup', 'kbd', 'abbr', 's', 'del', 'ins', 'wbr', 'font'];

    public const DIRETIVAS = [
        'if', 'elseif', 'else', 'endif', 'unless', 'endunless', 'isset', 'endisset', 'empty', 'endempty', 'auth', 'endauth',
        'guest', 'endguest', 'production', 'endproduction', 'env', 'endenv', 'hassection', 'sectionmissing', 'switch', 'case',
        'break', 'default', 'endswitch', 'for', 'endfor', 'foreach', 'endforeach', 'forelse', 'endforelse', 'while', 'endwhile',
        'continue', 'include', 'includeif', 'includewhen', 'includeunless', 'includefirst', 'each', 'extends', 'section',
        'endsection', 'show', 'yield', 'parent', 'stack', 'push', 'endpush', 'pushif', 'endpushif', 'pushonce', 'endpushonce',
        'prepend', 'endprepend', 'prependonce', 'endprependonce', 'once', 'endonce', 'php', 'endphp', 'verbatim', 'endverbatim',
        'props', 'aware', 'class', 'style', 'checked', 'selected', 'disabled', 'readonly', 'required', 'json', 'js', 'can',
        'cannot', 'canany', 'endcan', 'endcannot', 'endcanany', 'elsecan', 'elsecannot', 'elsecanany', 'lang', 'choice', 'error',
        'enderror', 'csrf', 'method', 'vite', 'livewire', 'livewirestyles', 'livewirescripts', 'livewirescriptconfig', 'persist',
        'endpersist', 'teleport', 'endteleport', 'island', 'endisland', 'entangle', 'this', 'assets', 'endassets', 'script',
        'endscript', 'inject', 'use', 'component', 'endcomponent', 'slot', 'endslot', 'dd', 'dump', 'session', 'endsession',
        'fragment', 'endfragment', 'bool', 'role', 'endrole', 'hasrole', 'endhasrole', 'hasanyrole', 'endhasanyrole',
        'hasallroles', 'endhasallroles', 'unlessrole', 'endunlessrole',
    ];

    private const DIRETIVAS_COM_TEXTO = ['section', 'yield', 'include', 'includeif', 'includewhen', 'includeunless', 'includefirst', 'each', 'component', 'livewire', 'slot', 'lang', 'choice'];

    private const ATTR_HTML = ['title', 'placeholder', 'alt', 'aria-label', 'aria-description', 'aria-placeholder', 'label', 'wire:confirm', 'data-confirm', 'data-tooltip', 'data-title', 'data-bs-title', 'data-bs-content', 'data-content'];

    private const ATTR_COMPONENTE = ['title', 'label', 'placeholder', 'description', 'text', 'message', 'heading', 'subheading', 'subtitle', 'hint', 'helper', 'caption', 'header', 'footer', 'legend', 'empty', 'confirm', 'tooltip', 'alt', 'aria-label', 'button', 'cancel', 'submit', 'confirm-text', 'cancel-text', 'submit-text', 'empty-text', 'content', 'badge'];

    private const ATTR_TECNICOS = ['class', 'id', 'name', 'type', 'href', 'src', 'action', 'method', 'for', 'style', 'role', 'target', 'rel', 'key', 'value', 'min', 'max', 'step', 'pattern', 'autocomplete', 'accept', 'lang', 'dir', 'tabindex', 'width', 'height', 'colspan', 'rowspan'];

    /**
     * @return array<int,array{texto:string,linha:int,tipo:string}>
     */
    public static function extrair(string $code, bool $blade, int $linhaBase = 1, bool $todas = false, string $tipoTexto = 'view'): array
    {
        $out = [];
        $work = Textos::utf8($code);

        $work = self::apagar('/<!--.*?-->/s', $work);
        $work = self::apagar('/<!DOCTYPE[^>]*>/i', $work);

        if ($blade) {
            $work = self::apagar('/\{\{--.*?--\}\}/s', $work);

            $php = function (string $inner, int $linha) use (&$out, $linhaBase, $todas): void {
                foreach (ExtratorPhp::extrair('<?php ' . $inner, $linhaBase + $linha - 1, false, $todas) as $t) {
                    $out[] = $t;
                }
            };
            $js = function (string $inner, int $linha) use (&$out, $linhaBase, $todas): void {
                foreach (ExtratorJs::extrair($inner, $linhaBase + $linha - 1, $todas) as $t) {
                    $out[] = $t;
                }
            };
            $work = self::blocos('/<\?(?:php|=)(.*?)(?:\?>|\z)/s', $work, $php);
            $work = self::blocos('/@php\b(?!\s*\()(.*?)@endphp/is', $work, $php);
            $work = self::blocos('/@script\b(.*?)@endscript/is', $work, $js);
        }

        $jsTag = function (string $inner, int $linha) use (&$out, $linhaBase, $todas): void {
            foreach (ExtratorJs::extrair($inner, $linhaBase + $linha - 1, $todas) as $t) {
                $out[] = $t;
            }
        };
        $work = self::blocos('/<script\b[^>]*>(.*?)<\/script>/is', $work, $jsTag);
        $work = self::apagar('/<style\b[^>]*>.*?<\/style>/is', $work);

        // {{ expr }} e {!! expr !!} viram marcadores — a frase continua inteira
        $ecos = [];
        if ($blade) {
            $snap = $work;
            $work = preg_replace_callback(
                '/(?<!@)\{\{(?!--)(.*?)\}\}|(?<!@)\{!!(.*?)!!\}/s',
                function (array $m) use (&$ecos, &$out, $snap, $linhaBase, $todas): string {
                    $cru = $m[0][0];
                    $bruto = isset($m[2]) && $m[2][1] >= 0;
                    $expr = $bruto ? $m[2][0] : $m[1][0];
                    $linha = $linhaBase + substr_count($snap, "\n", 0, $m[0][1]);

                    // dentro de uma tag (atributo)? então não relaxa: class="{{ $a ? 'ativo' : '' }}" é CSS
                    $ini = max(0, $m[0][1] - 1500);
                    $antes = (string) preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', '', substr($snap, $ini, $m[0][1] - $ini));
                    $lt = strrpos($antes, '<');
                    $gt = strrpos($antes, '>');
                    $dentroTag = $lt !== false && ($gt === false || $lt > $gt);

                    self::literais($expr, $linha, $out, $todas, ! $dentroTag);

                    $e = trim((string) preg_replace('/\s+/', ' ', $expr));
                    $repr = strlen($e) > 40 ? '…' : $e;
                    $ecos[] = $bruto ? '{!! ' . $repr . ' !!}' : '{{ ' . $repr . ' }}';

                    return "\x01" . (count($ecos) - 1) . "\x02" . str_repeat("\n", substr_count($cru, "\n"));
                },
                $work,
                -1,
                $c,
                PREG_OFFSET_CAPTURE
            ) ?? $work;

            $work = self::diretivas($work, $linhaBase, $out, $todas);
        }

        // texto entre as tags (tags inline como <strong> não quebram a frase)
        preg_match_all('/<\/?[A-Za-z][\w:.\-]*(?:"[^"]*"|\'[^\']*\'|[^>"\'])*>/s', $work, $achados, PREG_OFFSET_CAPTURE);

        $linhaAtual = $linhaBase;
        $posLinha = 0;
        $linhaEm = function (int $offset) use (&$linhaAtual, &$posLinha, $work): int {
            if ($offset > $posLinha) {
                $linhaAtual += substr_count($work, "\n", $posLinha, $offset - $posLinha);
                $posLinha = $offset;
            }

            return $linhaAtual;
        };

        $pos = 0;
        $buffer = '';
        $bufLinha = null;
        $pular = false;

        foreach ($achados[0] as [$tag, $off]) {
            self::acumular(substr($work, $pos, $off - $pos), $pos, $buffer, $bufLinha, $pular, $linhaEm);
            $pos = $off + strlen($tag);

            if (preg_match('/^<(\/?)([A-Za-z][\w:.\-]*)/', $tag, $mt) !== 1) {
                continue;
            }
            $fechando = $mt[1] === '/';
            $nome = strtolower($mt[2]);

            if (! $fechando) {
                self::atributos($tag, $nome, $linhaEm($off), $ecos, $out, $todas);
                if (preg_match('/class\s*=\s*["\'][^"\']*\bmaterial-(?:icons|symbols)/i', $tag) === 1) {
                    $pular = true; // <span class="material-icons">edit</span>: "edit" é ícone, não texto
                }
            } else {
                $pular = false;
            }

            if (! in_array($nome, self::INLINE, true)) {
                self::descarregar($buffer, $bufLinha, $ecos, $out, $todas, $tipoTexto);
            }
        }
        self::acumular(substr($work, $pos), $pos, $buffer, $bufLinha, $pular, $linhaEm);
        self::descarregar($buffer, $bufLinha, $ecos, $out, $todas, $tipoTexto);

        return $out;
    }

    private static function acumular(string $seg, int $pos, string &$buffer, ?int &$bufLinha, bool &$pular, \Closure $linhaEm): void
    {
        if (trim($seg) === '') {
            if ($buffer !== '') {
                $buffer .= $seg;
            }

            return;
        }
        if ($pular) {
            $pular = false;

            return;
        }
        if ($buffer === '') {
            $inicio = strlen($seg) - strlen(ltrim($seg));
            $bufLinha = $linhaEm($pos + $inicio);
        }
        $buffer .= $seg;
    }

    private static function descarregar(string &$buffer, ?int &$bufLinha, array $ecos, array &$out, bool $todas, string $tipo): void
    {
        if ($buffer === '') {
            return;
        }
        $txt = Textos::decodificarEcos($buffer, $ecos);
        $txt = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $txt = Textos::normalizar($txt);

        if ($txt !== '') {
            $sem = Textos::semPlaceholders($txt);
            if (Textos::temLetra($sem) && ($todas || preg_match('/->|::|=>|<\?/', $sem) !== 1)) {
                $out[] = ['texto' => $txt, 'linha' => $bufLinha ?? 1, 'tipo' => $tipo];
            }
        }
        $buffer = '';
        $bufLinha = null;
    }

    private static function atributos(string $tag, string $nome, int $linha, array $ecos, array &$out, bool $todas): void
    {
        if (preg_match_all('/([:@A-Za-z_][\w:.\-@\[\]]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/s', $tag, $ms, PREG_SET_ORDER) < 1) {
            return;
        }

        $componente = str_starts_with($nome, 'x-') || str_contains($nome, ':') || str_starts_with($nome, 'livewire');
        $tipoInput = null;
        if ($nome === 'input' && preg_match('/\btype\s*=\s*["\']?(\w+)/i', $tag, $mt) === 1) {
            $tipoInput = strtolower($mt[1]);
        }
        $meta = $nome === 'meta'
            && preg_match('/\b(?:name|property)\s*=\s*["\'](?:description|og:title|og:description|twitter:title|twitter:description)["\']/i', $tag) === 1;

        foreach ($ms as $m) {
            $an = strtolower($m[1]);
            $av = $m[2];
            if (isset($m[3]) && $m[3] !== '') {
                $av = $m[3];
            }
            $av = html_entity_decode(Textos::decodificarEcos($av, $ecos), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // atributos que carregam expressão (Alpine, onclick, :label="'Texto'")
            if ($an[0] === ':' || $an[0] === '@' || str_starts_with($an, 'x-') || preg_match('/^on[a-z]+$/', $an) === 1) {
                self::literaisGenericos($av, $linha, 'atributo', $out, $todas);
                continue;
            }

            $lista = $componente ? self::ATTR_COMPONENTE : self::ATTR_HTML;
            $forcado = in_array($an, $lista, true) || in_array($an, self::ATTR_HTML, true);
            if ($nome === 'input' && $an === 'value' && in_array($tipoInput, ['submit', 'button', 'reset'], true)) {
                $forcado = true;
            }
            if ($meta && $an === 'content') {
                $forcado = true;
            }
            if (! $forcado) {
                if (! $componente || in_array($an, self::ATTR_TECNICOS, true)
                    || str_starts_with($an, 'wire:') || str_starts_with($an, 'data-') || str_starts_with($an, 'aria-')) {
                    continue;
                }
            }

            $txt = Textos::normalizar($av);
            if ($txt !== '' && Textos::pareceTexto($txt, $forcado, $todas)) {
                $out[] = ['texto' => $txt, 'linha' => $linha, 'tipo' => 'atributo'];
            }
        }
    }

    /** Strings dentro de {{ ... }}: traduções (__('x')) e literais ($ok ? 'Ativo' : 'Inativo'). */
    private static function literais(string $expr, int $linha, array &$out, bool $todas, bool $relaxarTernario = false): void
    {
        $re = '/(?:\b__|\btrans_choice|\btrans|\bLang::get|\bLang::choice)\s*\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/s';
        $resto = preg_replace_callback($re, function (array $m) use (&$out, $linha, $todas): string {
            $v = $m[1];
            if ($v === '' && isset($m[2])) {
                $v = $m[2];
            }
            $v = Textos::normalizar(stripcslashes($v));
            if ($v !== '' && Textos::pareceTexto($v, true, $todas)) {
                $out[] = ['texto' => $v, 'linha' => $linha, 'tipo' => 'traducao'];
            }

            return ' ';
        }, $expr);

        self::literaisGenericos($resto ?? $expr, $linha, 'expressao', $out, $todas, $relaxarTernario);
    }

    /**
     * Strings entre aspas dentro de uma expressão. Com $relaxarTernario, uma
     * palavra solta logo após ? : ?? ou ?: ($ok ? 'aberto' : 'fechado') conta
     * como texto mesmo em minúscula — é o que aparece na tela.
     */
    private static function literaisGenericos(string $s, int $linha, string $tipo, array &$out, bool $todas, bool $relaxarTernario = false): void
    {
        if (preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)"/s', $s, $ms, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) < 1) {
            return;
        }
        foreach ($ms as $m) {
            $v = $m[1][0];
            if ($v === '' && isset($m[2])) {
                $v = $m[2][0];
            }
            $v = Textos::normalizar(stripcslashes($v));
            if ($v === '') {
                continue;
            }

            $forcado = $relaxarTernario
                && preg_match('/^\p{L}{2,}$/u', $v) === 1
                && preg_match('/(?:\?\?|\?:|\?|:)\s*$/', substr($s, 0, $m[0][1])) === 1;

            if (Textos::pareceTexto($v, $forcado, $todas)) {
                $out[] = ['texto' => $v, 'linha' => $linha, 'tipo' => $tipo];
            }
        }
    }

    /** Remove @diretivas(...) do Blade (com parênteses balanceados) e aproveita os textos de algumas. */
    private static function diretivas(string $work, int $linhaBase, array &$out, bool $todas): string
    {
        if (preg_match_all('/(?<![\w@.\-])@([A-Za-z_]\w*)(?![\w.\-:]*\s*=)/', $work, $c, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) < 1) {
            return $work;
        }

        $res = '';
        $cursor = 0;
        $len = strlen($work);

        foreach ($c as $m) {
            $pos = $m[0][1];
            if ($pos < $cursor) {
                continue;
            }
            $nome = strtolower($m[1][0]);
            if (! in_array($nome, self::DIRETIVAS, true)) {
                continue;
            }

            $fimNome = $pos + strlen($m[0][0]);
            $j = $fimNome;
            while ($j < $len && ($work[$j] === ' ' || $work[$j] === "\t")) {
                $j++;
            }
            $fim = $fimNome;
            $args = null;
            if ($j < $len && $work[$j] === '(') {
                $f = self::fechar($work, $j);
                $args = substr($work, $j + 1, $f - $j - 1);
                $fim = $f + 1;
            }

            $res .= substr($work, $cursor, $pos - $cursor) . self::branco(substr($work, $pos, $fim - $pos));
            $cursor = $fim;

            if ($args === null || ! in_array($nome, self::DIRETIVAS_COM_TEXTO, true)) {
                continue;
            }
            $linha = $linhaBase + substr_count($work, "\n", 0, $pos);

            if ($nome === 'lang' || $nome === 'choice') {
                if (preg_match('/^\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/s', $args, $mm) === 1) {
                    $v = $mm[1];
                    if ($v === '' && isset($mm[2])) {
                        $v = $mm[2];
                    }
                    $v = Textos::normalizar(stripcslashes($v));
                    if ($v !== '' && Textos::pareceTexto($v, true, $todas)) {
                        $out[] = ['texto' => $v, 'linha' => $linha, 'tipo' => 'traducao'];
                    }
                }
                continue;
            }
            self::literaisGenericos($args, $linha, 'expressao', $out, $todas);
        }

        return $res . substr($work, $cursor);
    }

    /** Índice do ')' que fecha o '(' em $abre, ignorando parênteses dentro de strings. */
    public static function fechar(string $s, int $abre): int
    {
        $len = strlen($s);
        $depth = 0;
        $aspas = null;
        for ($i = $abre; $i < $len; $i++) {
            $c = $s[$i];
            if ($aspas !== null) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $aspas) {
                    $aspas = null;
                }
                continue;
            }
            if ($c === "'" || $c === '"') {
                $aspas = $c;
            } elseif ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return $len - 1;
    }

    public static function apagar(string $re, string $work): string
    {
        return preg_replace_callback($re, fn (array $m): string => self::branco($m[0]), $work) ?? $work;
    }

    /** Executa $cb(conteúdo, linha) para cada bloco e o apaga (mantendo quebras de linha). */
    public static function blocos(string $re, string $work, \Closure $cb): string
    {
        $res = preg_replace_callback($re, function (array $m) use ($work, $cb): string {
            $cb($m[1][0], substr_count($work, "\n", 0, $m[1][1]) + 1);

            return self::branco($m[0][0]);
        }, $work, -1, $c, PREG_OFFSET_CAPTURE);

        return $res ?? $work;
    }

    public static function branco(string $s): string
    {
        return (string) preg_replace('/[^\n]/', ' ', $s);
    }
}

final class ExtratorPhp
{
    private const CHAVES = [
        'message', 'messages', 'mensagem', 'mensagens', 'msg', 'title', 'titulo', 'título', 'label', 'rotulo', 'rótulo',
        'description', 'descricao', 'descrição', 'text', 'texto', 'subject', 'assunto', 'body', 'corpo', 'placeholder',
        'hint', 'help', 'helper', 'tooltip', 'error', 'erro', 'success', 'sucesso', 'warning', 'aviso', 'info', 'confirm',
        'confirmacao', 'confirmação', 'empty', 'button', 'botao', 'botão', 'heading', 'subtitle', 'subtitulo', 'legend',
        'content', 'conteudo', 'conteúdo', 'observacao', 'observação', 'obs', 'caption', 'footer',
    ];

    /**
     * @return array<int,array{texto:string,linha:int,tipo:string}>
     */
    public static function extrair(string $code, int $linhaBase = 1, bool $lang = false, bool $todas = false): array
    {
        $tokens = \PhpToken::tokenize(Textos::utf8($code));
        $n = count($tokens);
        $out = [];
        $sig = [];

        for ($i = 0; $i < $n; $i++) {
            $t = $tokens[$i];
            $id = $t->id;
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }
            $linha = $linhaBase + $t->line - 1;

            if ($id === T_INLINE_HTML) {
                foreach (ExtratorHtml::extrair($t->text, false, $linha, $todas, $lang ? 'lang' : 'php') as $x) {
                    $out[] = $x;
                }
                continue;
            }

            $valor = null;
            if ($id === T_CONSTANT_ENCAPSED_STRING) {
                $valor = self::desaspar($t->text);
            } elseif ($t->text === '"' || $id === T_START_HEREDOC) {
                [$valor, $i] = self::interpolada($tokens, $i, $n);
            }

            if ($valor !== null) {
                $chave = self::seguidoDeSeta($tokens, $i, $n);
                $forcado = self::contextoTexto($sig, $lang, $chave);
                if (! ($lang && $chave)) {
                    self::tratar($valor, $linha, $forcado, $lang, $todas, $out);
                }
                $sig[] = ['id' => T_CONSTANT_ENCAPSED_STRING, 'txt' => '"str"', 'val' => $valor];
            } else {
                $sig[] = ['id' => $id, 'txt' => $t->text, 'val' => null];
            }
            if (count($sig) > 4) {
                array_shift($sig);
            }
        }

        return $out;
    }

    private static function tratar(string $valor, int $linha, bool $forcado, bool $lang, bool $todas, array &$out): void
    {
        $tipo = $lang ? 'lang' : 'php';

        if (Textos::temHtml($valor)) {
            foreach (ExtratorHtml::extrair($valor, false, $linha, $todas, $tipo) as $x) {
                $out[] = $x;
            }

            return;
        }

        $norm = Textos::normalizar($valor);
        if (Textos::pareceTexto($norm, $forcado, $todas)) {
            $out[] = ['texto' => $norm, 'linha' => $linha, 'tipo' => $tipo];
        }
    }

    /** 'message' => '...', msg: '...', __('...'), new XException('...') indicam texto mesmo sendo uma palavra só. */
    private static function contextoTexto(array $sig, bool $lang, bool $chave): bool
    {
        if ($lang) {
            return ! $chave;
        }
        $c = count($sig);
        $p1 = $sig[$c - 1] ?? null;
        $p2 = $sig[$c - 2] ?? null;
        if ($p1 === null || $p2 === null) {
            return false;
        }

        if ($p1['txt'] === '=>' && $p2['val'] !== null && in_array(Textos::minusculas($p2['val']), self::CHAVES, true)) {
            return true;
        }
        if ($p1['txt'] === ':' && $p2['id'] === T_STRING && in_array(strtolower($p2['txt']), self::CHAVES, true)) {
            return true;
        }
        if ($p1['txt'] === '(' && $p2['id'] === T_STRING) {
            $f = strtolower($p2['txt']);
            if (in_array($f, ['__', 'trans', 'trans_choice'], true) || str_ends_with($f, 'exception')) {
                return true;
            }
        }

        return false;
    }

    private static function seguidoDeSeta(array $tokens, int $i, int $n): bool
    {
        for ($j = $i + 1; $j < $n; $j++) {
            $id = $tokens[$j]->id;
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }

            return $tokens[$j]->text === '=>';
        }

        return false;
    }

    private static function desaspar(string $literal): string
    {
        $q = $literal[0] ?? '';
        $inner = substr($literal, 1, -1);

        return $q === "'" ? strtr($inner, ['\\\\' => '\\', "\\'" => "'"]) : stripcslashes($inner);
    }

    /**
     * Lê "..." com variáveis ou heredoc e devolve o texto inteiro, com as
     * variáveis como {$nome} no meio da frase.
     *
     * @return array{0:string,1:int} [texto, índice do último token]
     */
    private static function interpolada(array $tokens, int $i, int $n): array
    {
        $inicio = $tokens[$i];
        $heredoc = $inicio->id === T_START_HEREDOC;
        $nowdoc = $heredoc && str_contains($inicio->text, "'");
        $buf = '';

        for ($j = $i + 1; $j < $n; $j++) {
            $t = $tokens[$j];

            if ($heredoc && $t->id === T_END_HEREDOC) {
                return [$buf, $j];
            }
            if (! $heredoc && $t->text === '"') {
                return [$buf, $j];
            }

            if ($t->id === T_ENCAPSED_AND_WHITESPACE) {
                $buf .= $nowdoc ? $t->text : stripcslashes($t->text);
                continue;
            }

            if ($t->id === T_VARIABLE) {
                $expr = $t->text;
                $nx = $tokens[$j + 1] ?? null;
                $nx2 = $tokens[$j + 2] ?? null;
                if ($nx !== null && $nx->id === T_OBJECT_OPERATOR && $nx2 !== null && $nx2->id === T_STRING) {
                    $expr .= '->' . $nx2->text;
                    $j += 2;
                } elseif ($nx !== null && $nx->text === '[') {
                    $expr .= '[';
                    for ($k = $j + 2; $k < $n && $tokens[$k]->text !== ']'; $k++) {
                        $expr .= $tokens[$k]->text;
                    }
                    $expr .= ']';
                    $j = $k;
                }
                $buf .= '{' . $expr . '}';
                continue;
            }

            if ($t->id === T_CURLY_OPEN || $t->id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $expr = '';
                $depth = 1;
                for ($j++; $j < $n; $j++) {
                    $x = $tokens[$j];
                    if ($x->text === '{' || $x->id === T_CURLY_OPEN || $x->id === T_DOLLAR_OPEN_CURLY_BRACES) {
                        $depth++;
                    } elseif ($x->text === '}') {
                        $depth--;
                        if ($depth === 0) {
                            break;
                        }
                    }
                    $expr .= $x->text;
                }
                $buf .= '{' . $expr . '}';
                continue;
            }

            $buf .= $t->text;
        }

        return [$buf, $n - 1];
    }
}

final class ExtratorJs
{
    /**
     * @return array<int,array{texto:string,linha:int,tipo:string}>
     */
    public static function extrair(string $code, int $linhaBase = 1, bool $todas = false): array
    {
        $code = Textos::utf8($code);
        $re = '~//[^\n]*|/\*.*?\*/|\'((?:[^\'\\\\\n]|\\\\.)*)\'|"((?:[^"\\\\\n]|\\\\.)*)"|`((?:[^`\\\\]|\\\\.)*)`~s';
        if (preg_match_all($re, $code, $ms, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) < 1) {
            return [];
        }

        $out = [];
        $linha = $linhaBase;
        $posLinha = 0;

        foreach ($ms as $m) {
            $v = null;
            foreach ([1, 2, 3] as $g) {
                if (isset($m[$g]) && $m[$g][1] >= 0) {
                    $v = $m[$g][0];
                    break;
                }
            }
            if ($v === null || $v === '') {
                continue;
            }

            $off = $m[0][1];
            $linha += substr_count($code, "\n", $posLinha, $off - $posLinha);
            $posLinha = $off;

            $v = stripcslashes($v);
            if (Textos::temHtml($v)) {
                foreach (ExtratorHtml::extrair($v, false, $linha, $todas, 'js') as $x) {
                    $out[] = $x;
                }
                continue;
            }
            $v = Textos::normalizar($v);
            if (Textos::pareceTexto($v, false, $todas)) {
                $out[] = ['texto' => $v, 'linha' => $linha, 'tipo' => 'js'];
            }
        }

        return $out;
    }
}

final class Extrator
{
    /**
     * Escolhe o extrator pelo tipo do arquivo.
     *
     * @return array<int,array{texto:string,linha:int,tipo:string}>
     */
    public static function extrair(string $rel, string $code, bool $todas = false): array
    {
        if (str_starts_with($rel, 'tests/')) {
            return [];
        }
        if (str_ends_with($rel, '.blade.php')) {
            return ExtratorHtml::extrair($code, true, 1, $todas);
        }
        if (str_ends_with($rel, '.php')) {
            $lang = preg_match('~(^|/)lang/~', $rel) === 1;

            return ExtratorPhp::extrair($code, 1, $lang, $todas);
        }
        if (str_ends_with($rel, '.js')) {
            return ExtratorJs::extrair($code, 1, $todas);
        }
        if (str_ends_with($rel, '.vue')) {
            return ExtratorHtml::extrair($code, false, 1, $todas);
        }
        if (str_ends_with($rel, '.json')) {
            $dados = json_decode($code, true);
            if (! is_array($dados)) {
                return [];
            }
            $out = [];
            $visitar = function ($v, $k = null) use (&$visitar, &$out, $todas): void {
                if (is_array($v)) {
                    foreach ($v as $kk => $vv) {
                        $visitar($vv, is_string($kk) ? $kk : null);
                    }

                    return;
                }
                foreach ([$k, is_string($v) ? $v : null] as $s) {
                    if ($s === null) {
                        continue;
                    }
                    $s = Textos::normalizar($s);
                    if (Textos::pareceTexto($s, true, $todas)) {
                        $out[] = ['texto' => $s, 'linha' => 1, 'tipo' => 'lang'];
                    }
                }
            };
            $visitar($dados);

            return $out;
        }

        return [];
    }
}

/**
 * Análise do tema escuro (dark mode): onde ele é citado, em qual tag HTML, com
 * qual classe principal e com qual estilo — para achar o mesmo componente
 * estilizado de jeitos diferentes e tags com cor clara sem o par escuro.
 */
final class TemaClasses
{
    /** Padrão extra (--dark-padrao) para classes que o projeto usa como "dark". */
    public static ?string $padraoExtra = null;

    private const PALETAS = 'slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose';

    private const COR_RE = '~^(?<p>bg|text|border(?:-[trblxyse])?|ring-offset|ring|divide|placeholder|outline|fill|stroke|accent|caret|decoration|from|via|to|shadow)-(?<c>white|black|transparent|current|inherit|(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-(?<s>\d{2,3})|\[#(?<h>[0-9a-fA-F]{3,8})\])(?:/(?:\d+|\[[^\]]+\]))?$~';

    private const EXATAS = [
        'block', 'inline', 'inline-block', 'inline-flex', 'flex', 'grid', 'table', 'contents', 'hidden', 'container', 'static',
        'fixed', 'absolute', 'relative', 'sticky', 'visible', 'invisible', 'collapse', 'isolate', 'truncate', 'uppercase',
        'lowercase', 'capitalize', 'italic', 'underline', 'overline', 'line-through', 'no-underline', 'antialiased', 'sr-only',
        'not-sr-only', 'row', 'border', 'rounded', 'shadow', 'ring', 'outline', 'transition', 'grow', 'shrink', 'blur', 'invert',
        'sepia', 'filter', 'resize', 'transform', 'group', 'peer', 'prose', 'clearfix', 'active', 'disabled', 'show', 'fade',
        'collapsed', 'float-left', 'float-right', 'clear', 'ring-offset',
    ];

    private const PREFIXOS = [
        'm', 'mx', 'my', 'mt', 'mr', 'mb', 'ml', 'ms', 'me', 'p', 'px', 'py', 'pt', 'pr', 'pb', 'pl', 'ps', 'pe', 'w', 'h', 'size',
        'min-w', 'min-h', 'max-w', 'max-h', 'gap', 'gap-x', 'gap-y', 'space-x', 'space-y', 'top', 'right', 'bottom', 'left', 'inset',
        'inset-x', 'inset-y', 'start', 'end', 'z', 'order', 'col', 'col-span', 'col-start', 'col-end', 'row-span', 'row-start',
        'row-end', 'basis', 'grow', 'shrink', 'flex', 'grid', 'grid-cols', 'grid-rows', 'auto-cols', 'auto-rows', 'rounded',
        'border', 'shadow', 'ring', 'outline', 'divide', 'bg', 'text', 'font', 'leading', 'tracking', 'opacity', 'fill', 'stroke',
        'object', 'overflow', 'overscroll', 'cursor', 'select', 'pointer-events', 'transition', 'duration', 'ease', 'delay',
        'animate', 'scale', 'rotate', 'translate', 'skew', 'origin', 'blur', 'brightness', 'contrast', 'grayscale', 'hue-rotate',
        'invert', 'saturate', 'sepia', 'backdrop', 'aspect', 'columns', 'break', 'align', 'justify', 'items', 'content', 'self',
        'place', 'decoration', 'underline', 'whitespace', 'float', 'mix-blend', 'bg-blend', 'accent', 'caret', 'scroll', 'snap',
        'touch', 'will-change', 'line-clamp', 'from', 'via', 'to', 'sr', 'd', 'fw', 'fs', 'lh', 'position', 'text-bg', 'g', 'gx',
        'gy', 'ratio', 'vh', 'vw', 'min-vh', 'min-vw', 'mw', 'mh', 'group', 'peer', 'ring-offset', 'divide-x', 'divide-y',
    ];

    private static ?string $prefixosRe = null;

    /** "md:dark:hover:bg-x" => [['md','dark','hover'], 'bg-x'] (respeitando [] e () de valores arbitrários). */
    public static function dividir(string $token): array
    {
        $partes = [];
        $atual = '';
        $nivel = 0;
        $len = strlen($token);
        for ($i = 0; $i < $len; $i++) {
            $c = $token[$i];
            if ($c === '[' || $c === '(') {
                $nivel++;
            } elseif ($c === ']' || $c === ')') {
                $nivel = max(0, $nivel - 1);
            }
            if ($c === ':' && $nivel === 0) {
                $partes[] = $atual;
                $atual = '';
                continue;
            }
            $atual .= $c;
        }

        return [$partes, $atual];
    }

    /** @return array{token:string,dark:bool,var:string,base:string,prop:?string,tom:?string,utilitaria:bool} */
    public static function analisar(string $token): array
    {
        [$vars, $base] = self::dividir($token);
        $dark = in_array('dark', $vars, true);
        $sem = array_values(array_filter($vars, fn (string $v): bool => $v !== 'dark'));
        sort($sem);
        $base = ltrim($base, '!');

        $r = [
            'token' => $token,
            'dark' => $dark,
            'var' => implode(':', $sem),
            'base' => $base,
            'prop' => null,
            'tom' => null,
            'utilitaria' => self::ehUtilitaria($base),
        ];

        if (preg_match(self::COR_RE, $base, $m) === 1) {
            $p = str_starts_with($m['p'], 'border') ? 'border' : $m['p'];
            $r['prop'] = $p;
            $s = ($m['s'] ?? '') !== '' ? (int) $m['s'] : null;
            $r['tom'] = self::tom($m['c'], $s, ($m['h'] ?? '') !== '' ? $m['h'] : null);
        } elseif ($dark) {
            // dark:bg-primary-800 / dark:bg-surface: cor própria do projeto — a propriedade vale pelo prefixo
            $r['prop'] = self::propDeDark($base);
        }

        return $r;
    }

    /** Propriedade (bg, text, border...) de uma classe dark: com cor fora da paleta padrão; null se não for cor. */
    private static function propDeDark(string $base): ?string
    {
        if (preg_match('~^(?<p>bg|text|border(?:-[trblxyse])?|ring-offset|ring|divide|placeholder|outline|fill|stroke|from|via|to|decoration|caret|accent)-(?<r>.+?)(?:/(?:\d+|\[[^\]]+\]))?$~', $base, $m) !== 1) {
            return null;
        }
        $p = str_starts_with($m['p'], 'border') ? 'border' : $m['p'];
        $r = $m['r'];

        if ($r[0] === '[') {
            return preg_match('~^\[(?:#|rgb|hsl|oklch|oklab|color:|var\(--(?:color|bg|text|border))~i', $r) === 1 ? $p : null;
        }

        $naoCor = [
            'text' => '~^(?:xs|sm|base|lg|xl|\d+xl|left|center|right|justify|start|end|wrap|nowrap|balance|pretty|ellipsis|clip|opacity-.*)$~',
            'bg' => '~^(?:fixed|local|scroll|clip-.*|origin-.*|repeat(?:-.*)?|no-repeat|auto|cover|contain|center|top|bottom|left|right|(?:left|right)-(?:top|bottom)|blend-.*|none|gradient-.*|linear-.*|radial-.*|conic-.*|opacity-.*)$~',
            'border' => '~^(?:\d+|solid|dashed|dotted|double|hidden|none|collapse|separate|spacing-.*|opacity-.*)$~',
            'ring' => '~^(?:\d+|inset|opacity-.*)$~',
            'divide' => '~^(?:x|y|x-reverse|y-reverse|\d+|solid|dashed|dotted|double|none|opacity-.*)$~',
            'outline' => '~^(?:none|dashed|dotted|double|solid|\d+|offset-.*)$~',
            'from' => '~^\d+%$~', 'via' => '~^\d+%$~', 'to' => '~^\d+%$~',
            'stroke' => '~^(?:\d+|none)$~',
            'decoration' => '~^(?:solid|double|dotted|dashed|wavy|auto|from-font|\d+|clone|slice)$~',
        ];
        if (isset($naoCor[$p]) && preg_match($naoCor[$p], $r) === 1) {
            return null;
        }

        return $p;
    }

    private static function tom(string $cor, ?int $shade, ?string $hex): ?string
    {
        if (in_array($cor, ['transparent', 'current', 'inherit'], true)) {
            return null;
        }
        if ($hex !== null) {
            $l = self::luminanciaHex($hex);
            if ($l === null) {
                return null;
            }

            return $l > 0.72 ? 'claro' : ($l < 0.35 ? 'escuro' : 'medio');
        }
        if ($cor === 'white') {
            return 'claro';
        }
        if ($cor === 'black') {
            return 'escuro';
        }

        $pal = explode('-', $cor)[0];
        $neutra = in_array($pal, ['slate', 'gray', 'zinc', 'neutral', 'stone'], true);
        $s = $shade ?? 500;
        if ($neutra ? $s <= 300 : $s <= 200) {
            return 'claro';
        }
        if ($neutra ? $s >= 600 : $s >= 700) {
            return 'escuro';
        }

        return 'medio';
    }

    /** Cor que só funciona no tema claro: fundo/borda clara ou texto escuro. */
    public static function precisaDark(?string $prop, ?string $tom): bool
    {
        if ($prop === null || $tom === null) {
            return false;
        }
        if (in_array($prop, ['text', 'placeholder', 'decoration', 'caret'], true)) {
            return $tom === 'escuro';
        }
        if (in_array($prop, ['bg', 'from', 'via', 'to', 'border', 'divide', 'ring', 'ring-offset', 'outline'], true)) {
            return $tom === 'claro';
        }

        return false;
    }

    public static function luminanciaHex(string $hex): ?float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3 || strlen($hex) === 4) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) < 6 || preg_match('/^[0-9a-fA-F]{6,8}$/', $hex) !== 1) {
            return null;
        }

        return (0.2126 * hexdec(substr($hex, 0, 2)) + 0.7152 * hexdec(substr($hex, 2, 2)) + 0.0722 * hexdec(substr($hex, 4, 2))) / 255;
    }

    /** Primeira cor de um valor CSS (#hex, rgb(), white/black...) como luminância 0..1. */
    public static function luminanciaCss(string $valor): ?float
    {
        if (preg_match('/#([0-9a-fA-F]{3,8})\b/', $valor, $m) === 1) {
            return self::luminanciaHex($m[1]);
        }
        if (preg_match('/rgba?\(\s*(\d{1,3})[\s,]+(\d{1,3})[\s,]+(\d{1,3})/i', $valor, $m) === 1) {
            return (0.2126 * (int) $m[1] + 0.7152 * (int) $m[2] + 0.0722 * (int) $m[3]) / 255;
        }
        if (preg_match('/(?<![\w-])(white|whitesmoke|snow|ghostwhite|black)(?![\w-])/i', $valor, $m) === 1) {
            return strtolower($m[1]) === 'black' ? 0.0 : 1.0;
        }

        return null;
    }

    /** Declaração CSS que só funciona no tema claro? ('precisa' ou null). */
    public static function tomCss(string $prop, string $valor): ?string
    {
        $prop = strtolower(trim($prop));
        $fundo = in_array($prop, ['background', 'background-color'], true);
        $borda = in_array($prop, ['border', 'border-color', 'border-top', 'border-bottom', 'border-left', 'border-right', 'outline', 'outline-color'], true);
        $texto = $prop === 'color';
        if (! $fundo && ! $borda && ! $texto) {
            return null;
        }
        $l = self::luminanciaCss($valor);
        if ($l === null) {
            return null;
        }

        return (($fundo || $borda) && $l > 0.72) || ($texto && $l < 0.35) ? 'precisa' : null;
    }

    /** Classe que só liga o tema escuro (class="dark"), não que o estiliza. */
    public static function ehMarcadorDark(string $token): bool
    {
        return preg_match('/^(?:dark|theme-dark|dark-theme|dark-mode|darkmode|is-dark)$/i', $token) === 1;
    }

    /** Casa com o padrão informado em --dark-padrao (classes dark próprias do projeto). */
    public static function ehDarkDoProjeto(string $token): bool
    {
        return self::$padraoExtra !== null && ! self::ehMarcadorDark($token) && @preg_match('~' . self::$padraoExtra . '~', $token) === 1;
    }

    public static function ehUtilitaria(string $base): bool
    {
        $b = ltrim($base, '-!');
        if ($b === '' || $b[0] === '[') {
            return true;
        }
        if (in_array($b, self::EXATAS, true)) {
            return true;
        }
        if (str_starts_with($b, 'list-')) {
            return preg_match('~^list-(?:none|disc|decimal|inside|outside|image-none)$~', $b) === 1;
        }
        if (self::$prefixosRe === null) {
            self::$prefixosRe = '~^(?:' . implode('|', array_map(fn (string $p): string => preg_quote($p, '~'), self::PREFIXOS)) . ')(?:-|$)~';
        }

        return preg_match(self::$prefixosRe, $b) === 1;
    }

    /**
     * Separa as classes de uma tag em: dark (variante dark:), luz (utilitárias de cor
     * sem dark) e sem (classes semânticas: card, btn, modal...).
     *
     * @return array{dark:array<int,string>,luz:array<int,array{token:string,chave:string,prop:string,precisa:bool}>,sem:array<int,string>}
     */
    public static function classificar(array $tokens): array
    {
        $dark = [];
        $darkG = [];
        $luz = [];
        $sem = [];

        foreach ($tokens as $item) {
            $t = is_array($item) ? $item['t'] : $item;
            $g = is_array($item) ? ($item['g'] ?? null) : null; // g = ramo de uma classe condicional (null = estática)
            $a = self::analisar($t);

            if ($a['dark'] || self::ehDarkDoProjeto($t)) {
                $dark[] = $t; // dark: do Tailwind, ou classe que casa com --dark-padrao
                $darkG[] = $g;
                continue;
            }
            if ($a['prop'] !== null) {
                $luz[] = ['token' => $t, 'chave' => $a['var'] . '|' . $a['prop'], 'prop' => $a['prop'], 'precisa' => self::precisaDark($a['prop'], $a['tom']), 'g' => $g];
            }
            if (! $a['utilitaria'] && $a['var'] === '' && ! self::ehMarcadorDark($t) && preg_match('/^[A-Za-z_][\w-]*$/', $t) === 1) {
                $sem[] = $t;
            }
        }

        return ['dark' => $dark, 'darkG' => $darkG, 'luz' => $luz, 'sem' => $sem];
    }
}

final class ExtratorCss
{
    /**
     * Lê CSS/SCSS e devolve as regras que estilizam o tema escuro (.dark .x, [data-theme=dark],
     * @media (prefers-color-scheme: dark), @variant dark, @apply dark:...) e as declarações
     * claras sem par escuro.
     *
     * @return array{regras:array,tags:array,claroCss:array}
     */
    public static function extrair(string $css, int $linhaBase = 1): array
    {
        $out = ['regras' => [], 'tags' => [], 'claroCss' => []];
        $css = (string) preg_replace_callback('~/\*.*?\*/~s', fn (array $m): string => (string) preg_replace('/[^\n]/', ' ', $m[0]), Textos::utf8($css));

        $n = strlen($css);
        $linha = $linhaBase;
        $pilha = [];
        $buf = '';
        $bufLinha = $linha;
        $aspas = null;
        $par = 0;

        for ($i = 0; $i < $n; $i++) {
            $c = $css[$i];
            if ($c === "\n") {
                $linha++;
            }
            if ($aspas !== null) {
                $buf .= $c;
                if ($c === '\\' && $i + 1 < $n) {
                    $buf .= $css[++$i];
                } elseif ($c === $aspas) {
                    $aspas = null;
                }
                continue;
            }
            if ($c === '"' || $c === "'") {
                $aspas = $c;
                $buf .= $c;
                continue;
            }
            if ($c === '(') {
                $par++;
            } elseif ($c === ')') {
                $par = max(0, $par - 1);
            }

            if ($par === 0 && $c === '{') {
                $pre = trim($buf);
                $topo = $pilha === [] ? null : $pilha[count($pilha) - 1];
                $paiSel = $topo['sel'] ?? '';
                $ehAt = str_starts_with($pre, '@');
                if ($ehAt) {
                    $sel = $paiSel;
                } elseif (str_contains($pre, '&')) {
                    $sel = str_replace('&', $paiSel, $pre);
                } else {
                    $sel = $paiSel !== '' ? $paiSel . ' ' . $pre : $pre;
                }
                $pilha[] = [
                    'pre' => $pre,
                    'sel' => $sel,
                    'dark' => ($topo['dark'] ?? false) || self::mencionaDark($pre),
                    'at' => $ehAt,
                    'linha' => $bufLinha,
                    'decl' => [],
                    'apply' => [],
                ];
                $buf = '';
            } elseif ($par === 0 && $c === ';') {
                self::declaracao($pilha, trim($buf));
                $buf = '';
            } elseif ($par === 0 && $c === '}') {
                self::declaracao($pilha, trim($buf));
                $buf = '';
                if ($pilha !== []) {
                    $frame = array_pop($pilha);
                    self::fechar($frame, $pilha, $out);
                }
            } else {
                if ($buf === '' && ctype_space($c)) {
                    continue;
                }
                if ($buf === '') {
                    $bufLinha = $linha;
                }
                $buf .= $c;
            }
        }

        return $out;
    }

    public static function mencionaDark(string $prelude): bool
    {
        $re = '/\.(?:dark|theme-dark|dark-theme|dark-mode|darkmode|is-dark)(?![\w-])|data-(?:bs-)?(?:theme|color-mode)\s*[~|^$*]?=\s*["\']?dark|prefers-color-scheme\s*:\s*dark|@variant\s+dark/i';

        return preg_match($re, $prelude) === 1;
    }

    private static function declaracao(array &$pilha, string $stmt): void
    {
        if ($pilha === [] || $stmt === '') {
            return;
        }
        $k = count($pilha) - 1;

        if (preg_match('/^@apply\s+(.+)$/is', $stmt, $m) === 1) {
            foreach (preg_split('/\s+/', trim($m[1]), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $t) {
                $pilha[$k]['apply'][] = rtrim($t, '!') === '' ? $t : $t;
            }

            return;
        }
        if ($stmt[0] !== '@' && str_contains($stmt, ':')) {
            [$p, $v] = explode(':', $stmt, 2);
            $pilha[$k]['decl'][] = [trim($p), trim($v)];
        }
    }

    private static function fechar(array $frame, array &$pilha, array &$out): void
    {
        $pai = $pilha === [] ? null : count($pilha) - 1;
        $decl = $frame['decl'];
        $apply = $frame['apply'];

        if ($frame['at']) {
            $darkProprio = $frame['dark'] && ($pai === null || ! $pilha[$pai]['dark']);
            if ($darkProprio && $apply !== [] && $pai !== null) {
                // @variant dark { @apply x } dentro de .card vira dark:x no próprio .card
                foreach ($apply as $t) {
                    $pilha[$pai]['apply'][] = 'dark:' . $t;
                }
                $apply = [];
            }
            if ($frame['dark'] && ($decl !== [] || $apply !== [])) {
                self::regraDark($frame, $decl, $apply, $out);
            }

            return;
        }

        if ($frame['dark']) {
            if ($decl !== [] || $apply !== []) {
                self::regraDark($frame, $decl, $apply, $out);
            }

            return;
        }

        if ($apply !== []) {
            $principal = self::classesDe($frame['sel'])[0] ?? $frame['sel'];
            [$entrada] = ExtratorDark::montar($apply, '@apply', (int) $frame['linha'], ltrim((string) $principal, '.'));
            if ($entrada !== null) {
                $out['tags'][] = $entrada;
            }
        }
        foreach ($decl as [$p, $v]) {
            if (TemaClasses::tomCss($p, $v) === 'precisa') {
                foreach (self::classesDe($frame['sel']) as $cl) {
                    $out['claroCss'][] = ['linha' => (int) $frame['linha'], 'classe' => $cl, 'prop' => strtolower($p), 'valor' => $v];
                }
            }
        }
    }

    private static function regraDark(array $frame, array $decl, array $apply, array &$out): void
    {
        $partes = [];
        foreach ($decl as [$p, $v]) {
            $partes[] = $p . ': ' . $v;
        }
        if ($apply !== []) {
            $partes[] = '@apply ' . implode(' ', $apply);
        }

        $out['regras'][] = [
            'linha' => (int) $frame['linha'],
            'seletor' => $frame['sel'] !== '' ? $frame['sel'] : $frame['pre'],
            'classes' => self::classesDe($frame['sel']),
            'estilo' => Textos::cortar(implode('; ', $partes), 220),
        ];
    }

    /** @return array<int,string> classes (.x) de um seletor, sem os marcadores de dark. */
    public static function classesDe(string $sel): array
    {
        if (preg_match_all('/\.([A-Za-z_][\w-]*)/', $sel, $m) < 1) {
            return [];
        }

        return array_values(array_unique(array_filter($m[1], fn (string $c): bool => ! TemaClasses::ehMarcadorDark($c))));
    }
}

final class ExtratorDark
{
    private const PADROES = [
        // [regex, tipo, vale também em CSS?]
        ['/@custom-variant\s+dark|@variant\s+dark/i', 'variante dark (Tailwind v4)', true],
        ['/classList\.(?:add|remove|toggle|contains)\(\s*[\'"]dark/', 'alterna a classe dark (JS)', false],
        ['/\bdarkMode\s*[:=]/', 'config darkMode', false],
        ['/prefers-color-scheme/i', 'prefers-color-scheme', true],
        ['/(?<![\w-])color-scheme\b/i', 'color-scheme', true],
        ['/data-(?:bs-)?(?:theme|color-mode)|\[data-theme/i', 'atributo data-theme', false],
        ['/matchMedia\([^)]*dark/i', 'matchMedia dark', false],
        ['/localStorage[^;\n]*theme|theme[^;\n]*localStorage/i', 'tema salvo no navegador', false],
        ['/(?<![\w-])dark[-_]?(?:mode|theme)(?![\w-])|(?<![\w-])theme[-_]dark(?![\w-])|\bdarkMode\b|\bisDark\b/i', 'nome "modo escuro"', false],
        ['/(?:tema|modo)[-_ ]escuro/iu', 'texto/nome "tema escuro"', false],
        ['/(?:^|[\s,>+~(\[{])(?:html|body|:root)?\.dark(?![\w-])/', 'seletor CSS .dark', false],
        ['/\b(?:light|claro)\s*[,|]\s*dark\b|\bdark\s*[,|]\s*(?:light|claro)\b/i', 'opções de tema (light/dark)', false],
        ['/[\'"`]dark[\'"`]/', 'valor "dark"', false],
    ];

    /**
     * @return array{tags:array,ativacoes:array,citacoes:array,regras:array,claroCss:array,uso:array}
     */
    public static function extrair(string $rel, string $code): array
    {
        $r = ['tags' => [], 'ativacoes' => [], 'citacoes' => [], 'regras' => [], 'claroCss' => [], 'uso' => []];
        if (str_starts_with($rel, 'tests/') || preg_match('~\.min\.(?:css|js)$~', $rel) === 1) {
            return $r;
        }

        $code = Textos::utf8($code);
        $ehBlade = str_ends_with($rel, '.blade.php');
        $ehCss = preg_match('~\.s?css$~', $rel) === 1;
        $spans = [];

        // CSS de biblioteca compilado (Bootstrap, FontAwesome...) não é estilo do projeto
        if ($ehCss && (preg_match('~^\s*/\*!?.{0,400}?(?:bootstrap|font ?awesome|tailwindcss|normalize|select2|datatables|flatpickr|sweetalert|animate\.css|swiper|choices\.js)~is', $code) === 1
            || (str_starts_with($rel, 'public/') && strlen($code) > 200000))) {
            return $r;
        }

        if ($ehBlade || str_ends_with($rel, '.vue') || str_ends_with($rel, '.html')) {
            self::tags($code, $ehBlade, $r, $spans);
        }
        if ($ehCss) {
            $c = ExtratorCss::extrair($code);
            $r['regras'] = array_merge($r['regras'], $c['regras']);
            $r['tags'] = array_merge($r['tags'], $c['tags']);
            $r['claroCss'] = array_merge($r['claroCss'], $c['claroCss']);
        }
        self::citacoes($code, $ehBlade, $ehCss, $r, $spans);

        return $r;
    }

    /**
     * Monta a entrada de uma tag/regra: só existe se tiver classe dark ou cor que só vale no tema claro.
     *
     * @return array{0:?array,1:array}
     */
    public static function montar(array $tokens, string $tag, int $linha, ?string $principalFixo = null, bool $dinamico = false): array
    {
        $c = TemaClasses::classificar($tokens);
        $precisa = array_values(array_filter($c['luz'], fn (array $l): bool => $l['precisa']));

        if ($c['dark'] === [] && $precisa === []) {
            return [null, $c];
        }

        $principal = $principalFixo ?? ($c['sem'][0] ?? (str_starts_with($tag, 'x-') ? '<' . $tag . '>' : null));

        return [[
            'linha' => $linha,
            'tag' => $tag,
            'principal' => $principal,
            'sem' => $c['sem'],
            'dark' => $c['dark'],
            'darkG' => $c['darkG'],
            'luz' => $c['dark'] !== [] ? $c['luz'] : $precisa,
            'dinamico' => $dinamico,
        ], $c];
    }

    private static function tags(string $code, bool $blade, array &$r, array &$spans): void
    {
        $work = ExtratorHtml::apagar('/<!--.*?-->/s', $code);
        $nada = function (string $inner, int $linha): void {
        };

        if ($blade) {
            $work = ExtratorHtml::apagar('/\{\{--.*?--\}\}/s', $work);
            $work = ExtratorHtml::blocos('/<\?(?:php|=)(.*?)(?:\?>|\z)/s', $work, $nada);
            $work = ExtratorHtml::blocos('/@php\b(?!\s*\()(.*?)@endphp/is', $work, $nada);
            $work = ExtratorHtml::blocos('/@script\b(.*?)@endscript/is', $work, $nada);
        }
        $work = ExtratorHtml::blocos('/<script\b[^>]*>(.*?)<\/script>/is', $work, $nada);
        $work = ExtratorHtml::blocos('/<style\b[^>]*>(.*?)<\/style>/is', $work, function (string $inner, int $linha) use (&$r): void {
            $c = ExtratorCss::extrair($inner, $linha);
            $r['regras'] = array_merge($r['regras'], $c['regras']);
            $r['tags'] = array_merge($r['tags'], $c['tags']);
            $r['claroCss'] = array_merge($r['claroCss'], $c['claroCss']);
        });

        $ecos = [];
        $dirClasses = [];
        if ($blade) {
            $work = preg_replace_callback('/(?<!@)\{\{(?!--)(.*?)\}\}|(?<!@)\{!!(.*?)!!\}/s', function (array $m) use (&$ecos): string {
                $ecos[] = $m[0];

                return "\x01" . (count($ecos) - 1) . "\x02" . str_repeat("\n", substr_count($m[0], "\n"));
            }, $work) ?? $work;
            $work = self::diretivas($work, $dirClasses);
        }

        preg_match_all('/<\/?[A-Za-z][\w:.\-]*(?:"[^"]*"|\'[^\']*\'|[^>"\'])*>/s', $work, $achados, PREG_OFFSET_CAPTURE);

        $linhaAtual = 1;
        $posLinha = 0;
        foreach ($achados[0] as [$tag, $off]) {
            if (preg_match('/^<([A-Za-z][\w:.\-]*)/', $tag, $mt) !== 1) {
                continue;
            }
            if ($off > $posLinha) {
                $linhaAtual += substr_count($work, "\n", $posLinha, $off - $posLinha);
                $posLinha = $off;
            }
            $nome = strtolower($mt[1]);
            $linha = $linhaAtual;

            [$tokens, $dinamico, $ativ] = self::classesDaTag($tag, $ecos, $dirClasses);

            $trecho = null;
            $lazyTrecho = function () use ($tag, $ecos, $dirClasses): string {
                $t = Textos::decodificarEcos($tag, $ecos);
                $t = (string) preg_replace_callback('/data-dk-cls="(\d+)"/', fn (array $m): string => '@class(' . ($dirClasses[(int) $m[1]] ?? '') . ')', $t);

                return Textos::cortar(Textos::normalizar($t), 220);
            };

            if (preg_match('/dark|theme|escuro/i', $tag) === 1) {
                $spans[] = [$linha, $linha + substr_count($tag, "\n"), $nome];
            }

            $ehAtivClasse = false;
            foreach ($tokens as $t) {
                if (TemaClasses::ehMarcadorDark($t['t'])) {
                    $ehAtivClasse = true;
                    break;
                }
            }
            if ($ativ !== null || $ehAtivClasse) {
                $trecho = $lazyTrecho();
                $r['ativacoes'][] = [
                    'linha' => $linha,
                    'tag' => $nome,
                    'tipo' => $ativ['tipo'] ?? 'classe "dark" na tag',
                    'trecho' => $trecho,
                ];
            }

            if ($tokens === []) {
                continue;
            }

            [$entrada, $cls] = self::montar($tokens, $nome, $linha, null, $dinamico);
            foreach ($cls['sem'] as $sc) {
                $r['uso'][$sc]['total'] = ($r['uso'][$sc]['total'] ?? 0) + 1;
                if ($cls['dark'] !== []) {
                    $r['uso'][$sc]['dark'] = ($r['uso'][$sc]['dark'] ?? 0) + 1;
                }
            }
            if ($entrada !== null) {
                $r['tags'][] = $entrada;
            }
        }
    }

    /** @return array{0:array<int,string>,1:bool,2:?array} tokens de classe, se há classe dinâmica, ativação por atributo */
    private static function classesDaTag(string $tag, array $ecos, array $dirClasses): array
    {
        if (preg_match_all('/([:@A-Za-z_][\w:.\-@\[\]]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/s', $tag, $ms, PREG_SET_ORDER) < 1) {
            return [[], false, null];
        }

        $itens = [];
        $gid = 0;
        $dinamico = false;
        $ativ = null;
        // cada string entre aspas de uma expressão é um "ramo": 'a dark:b' : 'c' => ramos 1 e 2
        $add = function (array $grupos) use (&$itens, &$gid): void {
            foreach ($grupos as $grupo) {
                $gid++;
                foreach ($grupo as $t) {
                    $itens[] = ['t' => $t, 'g' => $gid];
                }
            }
        };

        foreach ($ms as $m) {
            $an = strtolower($m[1]);
            $av = $m[2];
            if (isset($m[3]) && $m[3] !== '') {
                $av = $m[3];
            }

            if ($an === 'class') {
                $estatico = (string) preg_replace_callback('/\x01(\d+)\x02/', function (array $mm) use (&$dinamico, $ecos, $add): string {
                    $dinamico = true;
                    $add(self::literaisDe($ecos[(int) $mm[1]] ?? ''));

                    return ' ';
                }, $av);
                foreach (preg_split('/\s+/', trim($estatico), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $t) {
                    if (self::tokenPlausivel($t)) {
                        $itens[] = ['t' => $t, 'g' => null];
                    }
                }
            } elseif ($an === ':class' || $an === 'x-bind:class' || $an === 'wire:class') {
                $dinamico = true;
                $add(self::literaisDe($av));
            } elseif ($an === 'data-dk-cls') {
                $dinamico = true;
                $add(self::literaisDe($dirClasses[(int) $av] ?? ''));
            } elseif (in_array($an, ['data-theme', 'data-bs-theme', ':data-theme', 'x-bind:data-theme', 'data-color-mode', 'data-color-scheme'], true)) {
                $ativ = ['tipo' => 'atributo ' . $an, 'valor' => Textos::decodificarEcos($av, $ecos)];
            }
        }

        return [$itens, $dinamico, $ativ];
    }

    private static function tokenPlausivel(string $t): bool
    {
        return preg_match('~^[!\-\w:\[\]/.#%&>*@,()=+^|]+$~', $t) === 1 && preg_match('/[A-Za-z]/', $t) === 1;
    }

    /** Strings entre aspas de uma expressão (Blade/Alpine), quebradas em tokens de classe. */
    private static function literaisDe(string $expr): array
    {
        if (preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)"/s', $expr, $ms, PREG_SET_ORDER) < 1) {
            return [];
        }
        $grupos = [];
        foreach ($ms as $m) {
            $v = $m[1];
            if ($v === '' && isset($m[2])) {
                $v = $m[2];
            }
            $tokens = [];
            foreach (preg_split('/\s+/', trim($v), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $t) {
                if (self::tokenPlausivel($t)) {
                    $tokens[] = $t;
                }
            }
            if ($tokens !== []) {
                $grupos[] = $tokens;
            }
        }

        return $grupos;
    }

    /** Troca @class([...]) por um atributo-marcador (para a classe poder ser lida) e apaga as demais diretivas. */
    private static function diretivas(string $work, array &$dirClasses): string
    {
        if (preg_match_all('/(?<![\w@.\-])@([A-Za-z_]\w*)(?![\w.\-:]*\s*=)/', $work, $c, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) < 1) {
            return $work;
        }

        $res = '';
        $cursor = 0;
        $len = strlen($work);

        foreach ($c as $m) {
            $pos = $m[0][1];
            if ($pos < $cursor) {
                continue;
            }
            $nome = strtolower($m[1][0]);
            if (! in_array($nome, ExtratorHtml::DIRETIVAS, true)) {
                continue;
            }

            $fimNome = $pos + strlen($m[0][0]);
            $j = $fimNome;
            while ($j < $len && ($work[$j] === ' ' || $work[$j] === "\t")) {
                $j++;
            }
            $fim = $fimNome;
            $args = null;
            if ($j < $len && $work[$j] === '(') {
                $f = ExtratorHtml::fechar($work, $j);
                $args = substr($work, $j + 1, $f - $j - 1);
                $fim = $f + 1;
            }

            $trecho = substr($work, $pos, $fim - $pos);
            $res .= substr($work, $cursor, $pos - $cursor);
            if ($nome === 'class' && $args !== null) {
                $dirClasses[] = $args;
                $res .= ' data-dk-cls="' . (count($dirClasses) - 1) . '"' . str_repeat("\n", substr_count($trecho, "\n"));
            } else {
                $res .= ExtratorHtml::branco($trecho);
            }
            $cursor = $fim;
        }

        return $res . substr($work, $cursor);
    }

    /** Linha a linha: onde o tema escuro é citado fora das classes dark: (config, JS, CSS, nomes, textos). */
    private static function citacoes(string $code, bool $ehBlade, bool $ehCss, array &$r, array $spans): void
    {
        $base = $ehBlade ? ExtratorHtml::apagar('/\{\{--.*?--\}\}|<!--.*?-->/s', $code) : $code;
        $jaTem = [];
        foreach ($r['ativacoes'] as $a) {
            $jaTem[$a['linha']] = true;
        }

        $padroes = self::PADROES;
        if (TemaClasses::$padraoExtra !== null) {
            $padroes[] = ['~' . TemaClasses::$padraoExtra . '~', 'padrão do projeto (--dark-padrao)', true];
        }

        foreach (explode("\n", $base) as $i => $linhaTxt) {
            $n = $i + 1;
            if (isset($jaTem[$n])) {
                continue;
            }
            $t = trim(rtrim($linhaTxt, "\r"));
            if ($t === '' || str_starts_with($t, '//') || str_starts_with($t, '*') || str_starts_with($t, '/*') || str_starts_with($t, '#')) {
                continue;
            }
            if (stripos($t, 'dark') === false && stripos($t, 'escuro') === false && stripos($t, 'color-scheme') === false
                && (TemaClasses::$padraoExtra === null)) {
                continue;
            }

            foreach ($padroes as [$re, $tipo, $emCss]) {
                if ($ehCss && ! $emCss) {
                    continue;
                }
                if (@preg_match($re, $t) !== 1) {
                    continue;
                }
                $tag = null;
                foreach ($spans as [$ini, $fim, $nome]) {
                    if ($n >= $ini && $n <= $fim) {
                        $tag = $nome;
                        break;
                    }
                }
                $r['citacoes'][] = ['linha' => $n, 'tipo' => $tipo, 'tag' => $tag, 'trecho' => Textos::cortar($t, 200)];
                break;
            }
        }
    }
}

/**
 * Inventário de verificações de feature flag, lido direto do código-fonte
 * (independe de estar dentro de método de classe): @feature(...),
 * @if(feature(...)), {{ feature(...) }}, if (! feature(...)), feature(...),
 * ->feature(...), Feature::active(...) e middleware 'feature:nome'.
 */
final class FeatureScanner
{
    private const PENNANT = 'active|inactive|allAreActive|someAreActive|allAreInactive|someAreInactive';

    /**
     * @return array<int,array{nome:string,linha:int,forma:string,dinamico:bool}>
     */
    public static function extrair(string $rel, string $code): array
    {
        if (str_starts_with($rel, 'tests/') || ! str_ends_with($rel, '.php')) {
            return [];
        }

        $src = Textos::utf8($code);
        $src = str_ends_with($rel, '.blade.php')
            ? ExtratorHtml::apagar('/\{\{--.*?--\}\}|<!--.*?-->/s', $src)
            : self::semComentarios($src);

        if (stripos($src, 'feature') === false) {
            return [];
        }

        $achados = []; // [offset, nome, forma, dinâmico]

        // 1. diretiva Blade: @feature('x'), @elsefeature('x'), @unlessfeature('x')
        if (preg_match_all('/(?<![\w@.\-])@(?:feature|elsefeature|unlessfeature)\s*\(/i', $src, $m, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($m[0] as [$txt, $off]) {
                self::chamada($src, $off, $off + strlen($txt) - 1, '@feature', $achados);
            }
        }

        // 2. feature('x') em qualquer contexto: PHP, @if(feature(...)), {{ feature(...) }}, if (! feature(...)), closures de rota
        if (preg_match_all('/(?<![\w$>:@])feature\s*\(/', $src, $m, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($m[0] as [$txt, $off]) {
                $antes = substr($src, max(0, $off - 30), min(30, $off));
                if (preg_match('/function\s*&?\s*$/i', $antes) === 1) {
                    continue; // definição do helper, não uma verificação
                }
                self::chamada($src, $off, $off + strlen($txt) - 1, 'feature()', $achados);
            }
        }

        // 3. método: $this->feature('x'), $user->feature('x')
        if (preg_match_all('/->\s*feature\s*\(/', $src, $m, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($m[0] as [$txt, $off]) {
                self::chamada($src, $off, $off + strlen($txt) - 1, '->feature()', $achados);
            }
        }

        // 4. Laravel Pennant: Feature::active('x') e Feature::for($u)->active('x')
        if (preg_match_all('/\bFeature::(' . self::PENNANT . ')\s*\(/', $src, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) > 0) {
            foreach ($m as $hit) {
                self::chamada($src, $hit[0][1], $hit[0][1] + strlen($hit[0][0]) - 1, 'Feature::' . $hit[1][0] . '()', $achados);
            }
        }
        if (preg_match_all('/\bFeature::for\s*\(/', $src, $m, PREG_OFFSET_CAPTURE) > 0) {
            foreach ($m[0] as [$txt, $off]) {
                $fecha = ExtratorHtml::fechar($src, $off + strlen($txt) - 1);
                if (preg_match('/\G\s*->\s*(' . self::PENNANT . ')\s*\(/', $src, $mm, PREG_OFFSET_CAPTURE, $fecha + 1) === 1) {
                    self::chamada($src, $off, $mm[0][1] + strlen($mm[0][0]) - 1, 'Feature::for()->' . $mm[1][0] . '()', $achados);
                }
            }
        }

        // 5. middleware: ->middleware('feature:x'), ['auth', 'feature:a,b']
        if (preg_match_all('/([\'"])([\w.\-]*feature[\w.\-]*):([^\'"\s]+)\1/i', $src, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) > 0) {
            foreach ($m as $hit) {
                $off = $hit[0][1];
                $contexto = substr($src, max(0, $off - 300), min(300, $off));
                if (stripos($contexto, 'middleware') === false) {
                    continue;
                }
                foreach (explode(',', $hit[3][0]) as $nome) {
                    $nome = trim($nome);
                    if ($nome !== '') {
                        $achados[] = [$off, $nome, 'middleware', false];
                    }
                }
            }
        }

        usort($achados, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $out = [];
        $linha = 1;
        $pos = 0;
        foreach ($achados as [$off, $nome, $forma, $dinamico]) {
            if ($off > $pos) {
                $linha += substr_count($src, "\n", $pos, $off - $pos);
                $pos = $off;
            }
            $out[] = ['nome' => $nome, 'linha' => $linha, 'forma' => $forma, 'dinamico' => $dinamico];
        }

        return $out;
    }

    private static function chamada(string $src, int $inicio, int $abre, string $forma, array &$achados): void
    {
        $fecha = ExtratorHtml::fechar($src, $abre);
        $args = substr($src, $abre + 1, $fecha - $abre - 1);
        foreach (self::nomes($args) as [$nome, $dinamico]) {
            $achados[] = [$inicio, $nome, $forma, $dinamico];
        }
    }

    /**
     * Nomes de feature no primeiro argumento. Literal vira nome fixo; qualquer outra
     * coisa ($var, 'a.' . $x, "a.$x") é registrada como dinâmica, para não sumir da contagem.
     *
     * @return array<int,array{0:string,1:bool}>
     */
    private static function nomes(string $args): array
    {
        $arg = trim(self::primeiroArgumento($args));
        if ($arg === '') {
            return [];
        }

        if ($arg[0] === '[') {
            $out = [];
            if (preg_match_all('/([\'"])((?:\\\\.|(?!\1).)*)\1/s', $arg, $ms, PREG_SET_ORDER) > 0) {
                foreach ($ms as $m) {
                    $out[] = [stripcslashes($m[2]), false];
                }
            }

            return $out !== [] ? $out : [[self::dinamico($arg), true]];
        }

        if (preg_match('/^([\'"])((?:\\\\.|(?!\1).)*)\1$/s', $arg, $m) === 1) {
            $interpolado = $m[1] === '"' && preg_match('/(?<!\\\\)\$|\{\$/', $m[2]) === 1;
            if (! $interpolado) {
                return [[stripcslashes($m[2]), false]];
            }
        }

        return [[self::dinamico($arg), true]];
    }

    private static function dinamico(string $expr): string
    {
        return '⟨dinâmico⟩ ' . Textos::cortar(Textos::normalizar($expr), 60);
    }

    /** Texto do primeiro argumento (até a primeira vírgula fora de parênteses, colchetes e strings). */
    private static function primeiroArgumento(string $args): string
    {
        $len = strlen($args);
        $depth = 0;
        $aspas = null;
        for ($i = 0; $i < $len; $i++) {
            $c = $args[$i];
            if ($aspas !== null) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $aspas) {
                    $aspas = null;
                }
                continue;
            }
            if ($c === "'" || $c === '"') {
                $aspas = $c;
            } elseif ($c === '(' || $c === '[' || $c === '{') {
                $depth++;
            } elseif ($c === ')' || $c === ']' || $c === '}') {
                $depth--;
            } elseif ($c === ',' && $depth === 0) {
                return substr($args, 0, $i);
            }
        }

        return $args;
    }

    /** Apaga comentários PHP (mantendo as quebras de linha) para não contar feature() comentado. */
    private static function semComentarios(string $src): string
    {
        $out = '';
        foreach (\PhpToken::tokenize($src) as $t) {
            $out .= ($t->id === T_COMMENT || $t->id === T_DOC_COMMENT)
                ? (string) preg_replace('/[^\n]/', ' ', $t->text)
                : $t->text;
        }

        return $out;
    }
}

final class Indexer
{
    /** Métodos do framework que não precisam existir na própria classe. */
    private const FRAMEWORK_METHODS = [
        'validate', 'validateonly', 'dispatch', 'dispatchself', 'dispatchto', 'dispatchbrowserevent',
        'emit', 'emitself', 'emitto', 'emitup', 'skiprender', 'redirect', 'redirectroute', 'redirectintended',
        'resetpage', 'gotopage', 'nextpage', 'previouspage', 'authorize', 'reset', 'resetexcept',
        'fill', 'only', 'except', 'adderror', 'reseterrorbag', 'resetvalidation', 'js', 'stream',
        'middleware', 'authorizeresource', 'json', 'response', 'call', 'render', 'boot', 'booted',
        'setuptesting', 'forgetcomputed', 'pull', 'has', 'get', 'set', 'all', 'toarray',
    ];

    /** Métodos de ciclo de vida: nunca são "órfãos". */
    private const LIFECYCLE = [
        '__construct', '__invoke', '__get', '__set', '__call', '__tostring',
        'mount', 'render', 'boot', 'booted', 'hydrate', 'dehydrate', 'updated', 'updating',
        'rules', 'messages', 'attributes', 'validationattributes', 'handle', 'run',
        'register', 'bootstrap', 'up', 'down', 'definition', 'configure', 'broadcaston',
        'via', 'tomail', 'toarray', 'todatabase', 'build', 'envelope', 'content', 'attachments',
        'authorize', 'passes', 'message', 'schedule', 'map', 'index', 'create', 'store',
        'show', 'edit', 'update', 'destroy', 'placeholder', 'paginationview',
    ];

    public array $classes = [];      // fqcn => info
    public array $arquivos = [];     // rel => struct
    public array $views = [];        // nome da view => rel
    public array $blades = [];       // rel => struct
    public array $viewOwner = [];    // nome da view => fqcn do componente
    public array $rotas = [];
    public array $callers = [];      // "Fqcn::metodo" => [origens]
    public array $modelUsage = [];   // model fqcn => ["Fqcn::metodo"]
    public array $eventos = [];      // evento => ['emissores'=>[], 'ouvintes'=>[]]
    public array $edges = [];        // [origem, destino, tipo]
    public array $gatilhos = [];     // todo dispatch/event/job disparado, com origem e parametros
    public array $tabelas = [];      // tabela => ['colunas'=>[nome=>info], 'origem'=>[arquivos]]
    public array $features = [];      // verificações de feature flag: nome, linha, forma, dinamico, arquivo
    public array $darkTags = [];      // tags/regras com dark: ou cor só-claro (arquivo, linha, tag, principal, dark, luz)
    public array $darkAtivacoes = []; // tags que ligam o tema (class="dark", data-theme...)
    public array $darkCitacoes = [];  // linhas que citam o tema escuro fora de classes (config, JS, CSS, nomes)
    public array $darkRegras = [];    // regras CSS do tema escuro
    public array $darkClaroCss = [];  // declarações CSS claras (candidatas a precisar de dark)
    public array $darkUso = [];       // classe semântica => ['total'=>n, 'dark'=>n] nas views
    public array $textos = [];       // texto => ['total'=>n, 'tipos'=>[tipo=>n], 'arquivos'=>[rel=>['n'=>n,'linhas'=>[...]]]]
    public array $avisos = [];
    public array $aliasComponentes = [];   // alias livewire => fqcn
    public array $viewsDaClasse = [];      // fqcn => [views]

    public function addPhpFile(array $file): void
    {
        $this->arquivos[$file['rel']] = $file;
        foreach ($file['classes'] as $class) {
            $class['namespace'] = $file['namespace'];
            $class['uses'] = $file['uses'];
            $this->classes[$class['fqcn']] = $class;
        }
    }

    public function addBlade(array $blade): void
    {
        $this->arquivos[$blade['rel']] = $blade;
        $this->blades[$blade['rel']] = $blade;
        $this->views[$blade['view']] = $blade['rel'];
    }

    public function addRoutes(array $rotas): void
    {
        foreach ($rotas as $rota) {
            $this->rotas[] = $rota;
        }
    }

    /** Componente Volt: a view É o componente, então liga o dono direto (sem precisar de render()). */
    public function addVoltComponent(string $view, array $class): void
    {
        $this->classes[$class['fqcn']] = $class;
        $this->viewOwner[$view] = $class['fqcn'];
    }

    /** Acumula as verificações de feature flag de um arquivo. */
    public function addFeatures(string $rel, array $lista): void
    {
        foreach ($lista as $f) {
            $f['arquivo'] = $rel;
            $this->features[] = $f;
        }
    }

    /** Acumula o que o extrator de tema escuro achou em um arquivo. */
    public function addDark(string $rel, array $d): void
    {
        foreach (['tags' => 'darkTags', 'ativacoes' => 'darkAtivacoes', 'citacoes' => 'darkCitacoes', 'regras' => 'darkRegras', 'claroCss' => 'darkClaroCss'] as $chave => $prop) {
            foreach ($d[$chave] as $item) {
                $item['arquivo'] = $rel;
                $this->{$prop}[] = $item;
            }
        }
        foreach ($d['uso'] as $classe => $u) {
            $this->darkUso[$classe]['total'] = ($this->darkUso[$classe]['total'] ?? 0) + ($u['total'] ?? 0);
            $this->darkUso[$classe]['dark'] = ($this->darkUso[$classe]['dark'] ?? 0) + ($u['dark'] ?? 0);
        }
    }

    /** Soma os textos de um arquivo ao inventário geral (texto exato => ocorrências por arquivo). */
    public function addTextos(string $rel, array $lista): void
    {
        foreach ($lista as $t) {
            $texto = (string) $t['texto'];
            if (! isset($this->textos[$texto])) {
                $this->textos[$texto] = ['total' => 0, 'tipos' => [], 'arquivos' => []];
            }
            $e = &$this->textos[$texto];
            $e['total']++;
            $e['tipos'][$t['tipo']] = ($e['tipos'][$t['tipo']] ?? 0) + 1;
            if (! isset($e['arquivos'][$rel])) {
                $e['arquivos'][$rel] = ['n' => 0, 'linhas' => []];
            }
            $e['arquivos'][$rel]['n']++;
            if (count($e['arquivos'][$rel]['linhas']) < 30) {
                $e['arquivos'][$rel]['linhas'][] = $t['linha'];
            }
            unset($e);
        }
    }

    /** Aplica, em ordem cronológica, as operações de uma migration sobre o estado acumulado do banco. */
    public function mergeMigration(array $operacoes): void
    {
        foreach ($operacoes as $op) {
            switch ($op['tipo']) {
                case 'create':
                    $this->tabelas[$op['tabela']] = [
                        'colunas' => $op['colunas'],
                        'origem' => [$op['arquivo']],
                    ];
                    break;

                case 'alter':
                    if (! isset($this->tabelas[$op['tabela']])) {
                        $this->tabelas[$op['tabela']] = ['colunas' => [], 'origem' => []];
                    }
                    foreach ($op['colunas'] as $nome => $col) {
                        $this->tabelas[$op['tabela']]['colunas'][$nome] = $col;
                    }
                    foreach ($op['remover'] ?? [] as $col) {
                        unset($this->tabelas[$op['tabela']]['colunas'][$col]);
                    }
                    $this->tabelas[$op['tabela']]['origem'][] = $op['arquivo'];
                    break;

                case 'drop':
                    unset($this->tabelas[$op['tabela']]);
                    break;

                case 'rename':
                    if (isset($this->tabelas[$op['de']])) {
                        $this->tabelas[$op['para']] = $this->tabelas[$op['de']];
                        unset($this->tabelas[$op['de']]);
                    }
                    break;
            }
        }
    }

    // ------------------------------------------------------------------

    public function build(): void
    {
        foreach ($this->classes as $fqcn => $class) {
            $this->classes[$fqcn]['categoria'] = $class['categoria'] ?? $this->classify($fqcn, $class);
        }

        $this->linkViews();
        $this->buildCallGraph();
        $this->linkBlades();
        $this->linkRoutes();
    }

    private function linkViews(): void
    {
        foreach ($this->classes as $fqcn => $class) {
            foreach ($class['metodos'] as $metodo) {
                foreach ($metodo['refs']['componentes_livewire'] ?? [] as $alias => $alvo) {
                    $this->aliasComponentes[strtolower($alias)] = $alvo;
                }
                foreach ($metodo['refs']['views'] as $view) {
                    $this->viewsDaClasse[$fqcn][] = $view;
                    if (! isset($this->viewOwner[$view]) && ($this->classes[$fqcn]['categoria'] ?? '') === 'livewire') {
                        $this->viewOwner[$view] = $fqcn;
                    }
                }
            }
            // convenção: App\...\Livewire\Period\RegrasManager -> livewire.period.regras-manager
            $convencao = $this->conventionalView($fqcn);
            if ($convencao !== null && ! isset($this->viewOwner[$convencao]) && ($class['categoria'] ?? '') === 'livewire') {
                $this->viewOwner[$convencao] = $fqcn;
            }
        }
    }

    private function buildCallGraph(): void
    {
        foreach ($this->classes as $fqcn => $class) {
            foreach ($class['metodos'] as $index => $metodo) {
                $origem = $fqcn . '::' . $metodo['nome'];
                $refs = $metodo['refs'];

                // chamadas internas
                $resolvidas = [];
                foreach ($refs['chamadas_internas'] as $alvo) {
                    $existe = $this->methodExists($fqcn, $alvo);
                    $resolvidas[] = ['metodo' => $alvo, 'existe' => $existe, 'framework' => in_array(strtolower($alvo), self::FRAMEWORK_METHODS, true)];
                    if ($existe) {
                        $this->addCaller($fqcn . '::' . $alvo, $origem);
                        $this->edges[] = [$fqcn, $fqcn, 'interna'];
                    }
                }
                $this->classes[$fqcn]['metodos'][$index]['refs']['chamadas_internas_detalhe'] = $resolvidas;

                // chamadas a outras classes
                foreach (['chamadas_estaticas', 'chamadas_instancia'] as $bucket) {
                    foreach ($refs[$bucket] as $alvoClasse => $metodos) {
                        if ($alvoClasse === '' || $alvoClasse === $fqcn) {
                            continue;
                        }
                        foreach ($metodos as $m) {
                            $this->addCaller($alvoClasse . '::' . $m, $origem);
                        }
                        if ($this->isModel($alvoClasse)) {
                            $this->modelUsage[$alvoClasse][] = $origem;
                        }
                        if (isset($this->classes[$alvoClasse]) || $this->isModel($alvoClasse)) {
                            $this->edges[] = [$fqcn, $alvoClasse, $bucket === 'chamadas_estaticas' ? 'static' : 'instancia'];
                        }
                    }
                }

                foreach ($refs['instanciacoes'] as $alvoClasse => $_) {
                    if (isset($this->classes[$alvoClasse])) {
                        $this->edges[] = [$fqcn, $alvoClasse, 'new'];
                        $this->addCaller($alvoClasse . '::__construct', $origem);
                    }
                    if ($this->isModel($alvoClasse)) {
                        $this->modelUsage[$alvoClasse][] = $origem;
                    }
                }

                // relacionamentos Eloquent
                foreach ($refs['relacionamentos'] ?? [] as $relacao) {
                    if ($relacao['classe'] !== '' && $relacao['classe'] !== $fqcn) {
                        $this->modelUsage[$relacao['classe']][] = $origem;
                        $this->edges[] = [$fqcn, $relacao['classe'], 'relacao'];
                    }
                }

                // Classe::class citada no corpo
                foreach (array_keys($refs['referencias_classe']) as $alvoClasse) {
                    if ($this->isModel($alvoClasse) && $alvoClasse !== $fqcn) {
                        $this->modelUsage[$alvoClasse][] = $origem;
                    }
                }

                // propriedades tipadas com model
                foreach ($refs['propriedades'] as $prop => $tipo) {
                    if ($this->isModel($tipo)) {
                        $this->modelUsage[$tipo][] = $origem;
                    }
                }

                // eventos livewire
                foreach ($refs['eventos_livewire'] as $evento) {
                    $this->eventos[$evento]['emissores'][] = $origem;
                }
                foreach ($refs['eventos'] as $evento) {
                    $this->eventos[$evento]['emissores'][] = $origem;
                }
                foreach ($refs['jobs'] as $job) {
                    $this->addCaller($job . '::handle', $origem);
                    $this->edges[] = [$fqcn, $job, 'job'];
                }

                foreach ($refs['gatilhos'] ?? [] as $gatilho) {
                    $this->gatilhos[] = array_merge($gatilho, ['origem' => $origem, 'arquivo' => $class['arquivo']]);
                }

                // #[On('evento')]
                foreach ($metodo['atributos'] as $attr) {
                    if (strtolower($attr['nome']) === 'on' && $attr['arg'] !== null) {
                        $this->eventos[$attr['arg']]['ouvintes'][] = $origem;
                    }
                }
            }

            // models declarados em propriedades da classe
            foreach ($class['propriedades'] as $nome => $prop) {
                if ($prop['tipo'] !== null && $this->isModel($prop['tipo'])) {
                    $this->modelUsage[$prop['tipo']][] = $fqcn . '::$' . $nome;
                }
            }
        }
    }

    private function linkBlades(): void
    {
        foreach ($this->blades as $rel => $blade) {
            $owner = $this->viewOwner[$blade['view']] ?? null;

            foreach ($blade['acoes'] as $acao) {
                if ($acao['metodo'] === null) {
                    continue;
                }
                if ($owner !== null && $this->methodExists($owner, $acao['metodo'])) {
                    $this->addCaller($owner . '::' . $acao['metodo'], "view `{$blade['view']}` ({$acao['evento']})");
                    $this->edges[] = ['view:' . $blade['view'], $owner, 'wire'];
                }
            }
            foreach ($blade['wire_js'] as $metodo) {
                if ($owner !== null && $this->methodExists($owner, $metodo)) {
                    $this->addCaller($owner . '::' . $metodo, "view `{$blade['view']}` (\$wire)");
                }
            }

            // componentes livewire renderizados dentro da view
            foreach ($blade['livewire'] as $nome) {
                $fqcn = $this->componentFromAlias($nome);
                if ($fqcn !== null) {
                    $this->edges[] = ['view:' . $blade['view'], $fqcn, 'render'];
                    $this->addCaller($fqcn . '::render', "view `{$blade['view']}`");
                }
            }
        }
    }

    private function linkRoutes(): void
    {
        foreach ($this->rotas as $chave => $rota) {
            if ($rota['classe'] === null) {
                continue;
            }
            $label = trim($rota['verbo'] . ' /' . ltrim((string) $rota['uri'], '/'));
            $nome = $rota['nome'] !== null ? " (name: {$rota['nome']})" : '';
            $metodo = $rota['metodo'];

            if ($metodo === '__invoke|render') {
                $metodo = $this->methodExists($rota['classe'], '__invoke') ? '__invoke' : 'render';
                $this->rotas[$chave]['metodo'] = $metodo;
            }

            $this->addCaller($rota['classe'] . '::' . $metodo, "rota `{$label}`{$nome}");
            if ($metodo !== 'render' && $this->methodExists($rota['classe'], 'mount')) {
                $this->addCaller($rota['classe'] . '::mount', "rota `{$label}`{$nome}");
            }
            if ($metodo === 'render' && $this->methodExists($rota['classe'], 'mount')) {
                $this->addCaller($rota['classe'] . '::mount', "rota `{$label}`{$nome}");
            }
            $this->edges[] = ['rota:' . $label, $rota['classe'], 'rota'];
        }
    }

    // ------------------------------------------------------------------

    public function methodExists(string $fqcn, string $metodo): bool
    {
        $class = $this->classes[$fqcn] ?? null;
        if ($class === null) {
            return false;
        }
        foreach ($class['metodos'] as $m) {
            if (strcasecmp($m['nome'], $metodo) === 0) {
                return true;
            }
        }
        foreach (array_merge($class['traits'], $class['extends']) as $parent) {
            if (isset($this->classes[$parent]) && $this->methodExists($parent, $metodo)) {
                return true;
            }
        }

        return false;
    }

    public function isModel(string $fqcn): bool
    {
        if ($fqcn === '') {
            return false;
        }
        if (isset($this->classes[$fqcn])) {
            return ($this->classes[$fqcn]['categoria'] ?? $this->classify($fqcn, $this->classes[$fqcn])) === 'model';
        }

        return str_contains($fqcn, '\\Models\\');
    }

    public function classify(string $fqcn, array $class): string
    {
        if (in_array($class['kind'], ['trait', 'interface', 'enum'], true)) {
            return $class['kind'];
        }

        $extends = array_map('strval', $class['extends']);
        $implements = array_map('strval', $class['implements']);
        $rel = $class['arquivo'];

        foreach ($extends as $parent) {
            if (str_contains($parent, 'Livewire\\Component') || str_contains($parent, 'Livewire\\Form')) {
                return 'livewire';
            }
            if (str_contains($parent, 'Eloquent\\Model') || str_contains($parent, 'Foundation\\Auth\\User') || str_contains($parent, 'Authenticatable')) {
                return 'model';
            }
            if (str_contains($parent, 'Eloquent\\Factories\\Factory')) {
                return 'factory';
            }
            if (str_contains($parent, 'Seeder')) {
                return 'seeder';
            }
            if (str_contains($parent, 'Console\\Command')) {
                return 'command';
            }
            if (str_contains($parent, 'ServiceProvider')) {
                return 'provider';
            }
            if (str_contains($parent, 'FormRequest')) {
                return 'request';
            }
            if (str_contains($parent, 'JsonResource') || str_contains($parent, 'ResourceCollection')) {
                return 'resource';
            }
            if (str_contains($parent, 'Mailable')) {
                return 'mail';
            }
            if (str_contains($parent, 'Notification')) {
                return 'notification';
            }
            if (str_contains($parent, 'TestCase')) {
                return 'teste';
            }
        }

        if (in_array('Illuminate\\Contracts\\Queue\\ShouldQueue', $implements, true) && str_contains($fqcn, '\\Jobs\\')) {
            return 'job';
        }

        $map = [
            '\\Models\\' => 'model',
            '\\Livewire\\' => 'livewire',
            '\\Http\\Controllers\\' => 'controller',
            '\\Jobs\\' => 'job',
            '\\Events\\' => 'event',
            '\\Listeners\\' => 'listener',
            '\\Notifications\\' => 'notification',
            '\\Mail\\' => 'mail',
            '\\Policies\\' => 'policy',
            '\\Observers\\' => 'observer',
            '\\Providers\\' => 'provider',
            '\\Console\\Commands\\' => 'command',
            '\\Http\\Middleware\\' => 'middleware',
            '\\Http\\Requests\\' => 'request',
            '\\Http\\Resources\\' => 'resource',
            '\\Rules\\' => 'rule',
            '\\Services\\' => 'service',
            '\\Repositories\\' => 'repository',
            '\\Actions\\' => 'action',
            '\\Enums\\' => 'enum',
            '\\Exports\\' => 'export',
            '\\Imports\\' => 'import',
            '\\DTO\\' => 'dto',
            '\\Support\\' => 'support',
        ];
        foreach ($map as $needle => $categoria) {
            if (str_contains($fqcn, $needle)) {
                return $categoria;
            }
        }

        if (str_ends_with($class['nome'], 'Controller')) {
            return 'controller';
        }
        if (str_starts_with($rel, 'database/migrations/')) {
            return 'migration';
        }
        if (str_starts_with($rel, 'database/seeders/')) {
            return 'seeder';
        }
        if (str_starts_with($rel, 'database/factories/')) {
            return 'factory';
        }
        if (str_starts_with($rel, 'tests/')) {
            return 'teste';
        }

        return 'classe';
    }

    public function isOrphan(string $fqcn, array $metodo): bool
    {
        if (in_array('private', $metodo['modificadores'], true) || in_array('protected', $metodo['modificadores'], true)) {
            return false;
        }
        if (in_array(strtolower($metodo['nome']), self::LIFECYCLE, true)) {
            return false;
        }
        if (str_starts_with(strtolower($metodo['nome']), 'updated') || str_starts_with(strtolower($metodo['nome']), 'updating')) {
            return false;
        }
        if (! empty($metodo['atributos'])) {
            return false; // #[On], #[Computed], #[Renderless]...
        }
        $categoria = $this->classes[$fqcn]['categoria'] ?? '';
        if (in_array($categoria, ['model', 'migration', 'factory', 'teste', 'enum', 'interface', 'trait'], true)) {
            return false;
        }

        return empty($this->callers[$fqcn . '::' . $metodo['nome']]);
    }

    public function callersOf(string $fqcn, string $metodo): array
    {
        return array_values(array_unique($this->callers[$fqcn . '::' . $metodo] ?? []));
    }

    private function addCaller(string $alvo, string $origem): void
    {
        $this->callers[$alvo][] = $origem;
    }

    private function conventionalView(string $fqcn): ?string
    {
        $pos = strrpos($fqcn, '\\Livewire\\');
        if ($pos === false) {
            return null;
        }
        $resto = substr($fqcn, $pos + strlen('\\Livewire\\'));
        $partes = array_map(
            fn (string $p): string => strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $p)),
            explode('\\', $resto)
        );

        return 'livewire.' . implode('.', $partes);
    }

    public function componentFromAlias(string $alias): ?string
    {
        $chave = strtolower(str_replace([':', '-'], ['.', '-'], $alias));
        if (isset($this->aliasComponentes[$chave])) {
            return $this->aliasComponentes[$chave];   // registrado via Livewire::component()
        }

        $normal = strtolower(str_replace(['.', ':'], '-', $alias));

        // 0. o alias bate direto com uma view conhecida (cobre Volt, cuja view É o componente)
        foreach ($this->views as $viewName => $relPath) {
            $limpo = str_replace('.', '-', (string) preg_replace('/^livewire\./', '', $viewName));
            if ($limpo === $normal && isset($this->viewOwner[$viewName])) {
                return $this->viewOwner[$viewName];
            }
        }

        // 1. a view que o componente renderiza bate com o alias
        foreach ($this->viewsDaClasse as $fqcn => $views) {
            if (($this->classes[$fqcn]['categoria'] ?? '') !== 'livewire') {
                continue;
            }
            foreach ($views as $view) {
                $limpo = str_replace('.', '-', (string) preg_replace('/^livewire\./', '', $view));
                if ($limpo === $normal) {
                    return $fqcn;
                }
            }
        }

        // 2. convenção de nome (App\Livewire\Foo\Bar => foo.bar), com tolerância a módulos
        foreach ($this->classes as $fqcn => $class) {
            if (($class['categoria'] ?? '') !== 'livewire') {
                continue;
            }
            $convencao = $this->conventionalView($fqcn);
            if ($convencao === null) {
                continue;
            }
            $kebab = str_replace('.', '-', substr($convencao, strlen('livewire.')));
            if ($kebab === $normal || str_ends_with($normal, $kebab)) {
                return $fqcn;
            }
        }

        return null;
    }
}

final class MarkdownWriter
{
    private const ROTULOS = [
        'livewire' => '⚡ Componentes Livewire',
        'controller' => '🎮 Controllers',
        'model' => '🗃️ Models',
        'job' => '⏳ Jobs',
        'event' => '📣 Events',
        'listener' => '👂 Listeners',
        'notification' => '🔔 Notifications',
        'mail' => '✉️ Mailables',
        'policy' => '🛡️ Policies',
        'observer' => '👁️ Observers',
        'middleware' => '🚦 Middlewares',
        'request' => '📥 Form Requests',
        'resource' => '📤 API Resources',
        'service' => '⚙️ Services',
        'action' => '🎯 Actions',
        'repository' => '📚 Repositories',
        'rule' => '✅ Regras de validação',
        'trait' => '🧬 Traits',
        'interface' => '🔌 Interfaces',
        'enum' => '🔢 Enums',
        'provider' => '🧩 Service Providers',
        'command' => '⌨️ Comandos Artisan',
        'migration' => '🧱 Migrations',
        'seeder' => '🌱 Seeders',
        'factory' => '🏭 Factories',
        'export' => '📦 Exports',
        'import' => '📦 Imports',
        'dto' => '📄 DTOs',
        'support' => '🔧 Support',
        'teste' => '🧪 Testes',
        'classe' => '📦 Outras classes',
    ];

    public function __construct(
        private Indexer $index,
        private array $scan,
        private string $projeto,
        private bool $mermaid = true,
        private bool $sequencia = true,
        private bool $erd = true,
        private bool $textos = true,
        private bool $palavras = false,
        private ?string $textosArquivo = null,
        private bool $dark = true,
        private ?string $darkArquivo = null,
    ) {
    }

    public function render(): string
    {
        $md = [];
        $md[] = $this->cabecalho();
        $md[] = $this->visaoGeral();
        $md[] = $this->pontosDeEntrada();
        if ($this->sequencia) {
            $md[] = $this->diagramasSequencia();
        }
        $md[] = $this->fluxoPorArquivo();
        $md[] = $this->viewsBlade();
        $md[] = $this->indiceReverso();
        $md[] = $this->modelsUsados();
        if ($this->erd) {
            $md[] = $this->diagramaBanco();
        }
        $md[] = $this->eventosLivewire();
        $md[] = $this->gatilhos();
        $md[] = $this->orfaos();
        if ($this->mermaid) {
            $md[] = $this->grafo();
        }
        $md[] = $this->arvoreArquitetura();
        $md[] = $this->permissoesFeatures();
        $md[] = $this->indiceFuncoes();
        if ($this->textos) {
            $md[] = $this->textosDoSistema();
        }
        if ($this->dark) {
            $md[] = $this->temaEscuro();
        }
        $md[] = $this->arquivosIgnorados();

        return implode("\n", array_filter($md)) . "\n";
    }

    // ------------------------------------------------------------------

    private function cabecalho(): string
    {
        $data = date('d/m/Y H:i');
        $total = count($this->scan['relevantes']);
        $ignorados = count($this->scan['padrao']);

        return <<<MD
        # 🗺️ Mapa do Sistema — {$this->projeto}

        > Gerado por **LaraMap** em {$data}
        > {$total} arquivos próprios analisados · {$ignorados} arquivos padrão do Laravel/pacotes ignorados

        ## Sumário

        1. [Visão geral](#visão-geral)
        2. [Pontos de entrada (rotas)](#pontos-de-entrada-rotas)
        3. [Diagramas de sequência](#diagramas-de-sequência)
        4. [Fluxo por arquivo](#fluxo-por-arquivo)
        5. [Views Blade (com botões e links)](#views-blade)
        6. [Índice reverso — quem chama quem](#índice-reverso--quem-chama-quem)
        7. [Models e onde são usados](#models-e-onde-são-usados)
        8. [Diagrama de banco de dados](#diagrama-de-banco-de-dados)
        9. [Eventos](#eventos)
        10. [Gatilhos — dispatch / disparar](#gatilhos-dispatch--disparar)
        11. [Métodos sem chamador conhecido](#métodos-sem-chamador-conhecido)
        12. [Grafo de dependências](#grafo-de-dependências)
        13. [Árvore da arquitetura](#árvore-da-arquitetura)
        14. [Permissões e Feature Flags](#permissões-e-feature-flags)
        15. [Índice de funções por arquivo](#índice-de-funções-por-arquivo)
        16. [Textos e strings do sistema](#textos-e-strings-do-sistema)
        17. [Tema escuro (dark mode)](#tema-escuro-dark-mode)
        18. [Arquivos ignorados](#arquivos-ignorados)

        ---
        MD;
    }

    private function visaoGeral(): string
    {
        $contagem = [];
        foreach ($this->index->classes as $class) {
            $cat = $class['categoria'] ?? 'classe';
            $contagem[$cat] = ($contagem[$cat] ?? 0) + 1;
        }
        arsort($contagem);

        $linhas = ["## Visão geral\n", '| Tipo | Quantidade |', '| --- | ---: |'];
        foreach ($contagem as $cat => $n) {
            $linhas[] = '| ' . (self::ROTULOS[$cat] ?? $cat) . ' | ' . $n . ' |';
        }
        $linhas[] = '| 🖼️ Views Blade | ' . count($this->index->blades) . ' |';
        $linhas[] = '| 🛣️ Rotas declaradas | ' . count($this->index->rotas) . ' |';

        $metodos = 0;
        foreach ($this->index->classes as $class) {
            $metodos += count($class['metodos']);
        }
        $linhas[] = '| 🔧 Métodos mapeados | ' . $metodos . ' |';

        if ($this->scan['modificados'] !== []) {
            $linhas[] = "\n**Arquivos do esqueleto que foram modificados:** " . implode(', ', array_map(
                fn ($f) => "`{$f}`",
                $this->scan['modificados']
            ));
        }

        return implode("\n", $linhas) . "\n\n---\n";
    }

    private function pontosDeEntrada(): string
    {
        $linhas = ["## Pontos de entrada (rotas)\n"];

        if ($this->index->rotas === []) {
            return $linhas[0] . "_Nenhuma rota encontrada nos arquivos analisados._\n\n---\n";
        }

        $linhas[] = '| Verbo | URI | Destino | Nome | Middleware |';
        $linhas[] = '| --- | --- | --- | --- | --- |';

        foreach ($this->index->rotas as $rota) {
            $destino = $rota['classe'] !== null
                ? '`' . $this->curto($rota['classe']) . '::' . $rota['metodo'] . '()`'
                : ($rota['view'] !== null ? 'view `' . $rota['view'] . '`' : '—');

            $linhas[] = sprintf(
                '| %s | `%s` | %s | %s | %s |',
                $rota['verbo'],
                '/' . ltrim((string) $rota['uri'], '/'),
                $destino,
                $rota['nome'] !== null ? '`' . $rota['nome'] . '`' : '—',
                $rota['middleware'] !== null ? '`' . str_replace('|', '\|', $rota['middleware']) . '`' : '—'
            );
        }

        return implode("\n", $linhas) . "\n\n---\n";
    }

    /** Um diagrama de sequência Mermaid por ponto de entrada (rota ou ação de view). */
    private function diagramasSequencia(): string
    {
        $alvos = $this->pontosDeEntradaParaSequencia();

        $out = ["## Diagramas de sequência\n"];
        if ($alvos === []) {
            return $out[0] . "_Nenhum ponto de entrada encontrado._\n\n---\n";
        }

        $out[] = "_Um diagrama por rota e por ação de view (`wire:click`/`wire:submit`). Chamadas internas (`\$this->metodo()`) são expandidas em até 3 níveis; chamadas para outras classes aparecem como uma única troca de mensagens, sem entrar no código delas._\n";

        $limite = 80;
        foreach (array_slice($alvos, 0, $limite) as $alvo) {
            $out[] = $this->umDiagramaSequencia($alvo['fqcn'], $alvo['metodo'], $alvo['ator'], $alvo['titulo']);
        }
        if (count($alvos) > $limite) {
            $out[] = '_...e mais ' . (count($alvos) - $limite) . " ponto(s) de entrada não mostrados (limite de {$limite} diagramas)._\n";
        }

        return implode("\n", $out) . "\n---\n";
    }

    /** @return array<int,array{fqcn:string,metodo:string,ator:string,titulo:string}> */
    private function pontosDeEntradaParaSequencia(): array
    {
        $vistos = [];
        $alvos = [];

        foreach ($this->index->rotas as $rota) {
            if ($rota['classe'] === null) {
                continue;
            }
            $metodo = $rota['metodo'];
            $categoria = $this->index->classes[$rota['classe']]['categoria'] ?? null;
            if ($categoria === 'livewire' && $this->index->methodExists($rota['classe'], 'mount')) {
                $metodo = 'mount';
            }
            if ($metodo === null || ! $this->index->methodExists($rota['classe'], $metodo)) {
                continue;
            }

            $chave = $rota['classe'] . '::' . $metodo;
            if (isset($vistos[$chave])) {
                continue;
            }
            $vistos[$chave] = true;

            $label = trim($rota['verbo'] . ' /' . ltrim((string) $rota['uri'], '/'));
            $alvos[] = [
                'fqcn' => $rota['classe'],
                'metodo' => $metodo,
                'ator' => 'Rota',
                'titulo' => $label . ($rota['nome'] !== null ? ' (' . $rota['nome'] . ')' : ''),
            ];
        }

        foreach ($this->index->blades as $blade) {
            $owner = $this->index->viewOwner[$blade['view']] ?? null;
            if ($owner === null) {
                continue;
            }
            foreach ($blade['acoes'] as $acao) {
                if ($acao['metodo'] === null || ! $this->index->methodExists($owner, $acao['metodo'])) {
                    continue;
                }
                $chave = $owner . '::' . $acao['metodo'];
                if (isset($vistos[$chave])) {
                    continue;
                }
                $vistos[$chave] = true;

                $alvos[] = [
                    'fqcn' => $owner,
                    'metodo' => $acao['metodo'],
                    'ator' => 'View',
                    'titulo' => $blade['view'] . ' — `' . $acao['evento'] . '`',
                ];
            }
        }

        return $alvos;
    }

    private function umDiagramaSequencia(string $fqcn, string $metodo, string $atorLabel, string $titulo): string
    {
        $ids = [];
        $participantes = [];
        $linhas = [];

        $idAtor = $this->idParticipante($atorLabel, $participantes, $ids);
        $idClasse = $this->idParticipante($this->curto($fqcn), $participantes, $ids);

        $linhas[] = "{$idAtor}->>+{$idClasse}: {$metodo}()";
        $this->passosSequencia($fqcn, $metodo, $idClasse, 1, [$fqcn . '::' . $metodo => true], $linhas, $participantes, $ids);
        $linhas[] = "{$idClasse}-->>-{$idAtor}: ";

        $corpo = "```mermaid\nsequenceDiagram\n";
        foreach ($participantes as $id => $label) {
            $corpo .= "    participant {$id} as {$label}\n";
        }
        foreach ($linhas as $l) {
            $corpo .= "    {$l}\n";
        }
        $corpo .= '```';

        return "### {$titulo}\n\n{$corpo}\n";
    }

    /** @param array<string,bool> $visitados */
    private function passosSequencia(string $fqcn, string $metodo, string $idAtual, int $profundidade, array $visitados, array &$linhas, array &$participantes, array &$ids): void
    {
        $refMetodo = $this->buscarMetodo($fqcn, $metodo);
        if ($refMetodo === null) {
            return;
        }

        foreach ($refMetodo['refs']['sequencia'] ?? [] as $passo) {
            switch ($passo['tipo']) {
                case 'self':
                    $chave = $fqcn . '::' . $passo['metodo'];
                    $existe = $this->index->methodExists($fqcn, $passo['metodo']);
                    if ($existe && $profundidade < 3 && ! isset($visitados[$chave])) {
                        $linhas[] = "{$idAtual}->>+{$idAtual}: {$passo['metodo']}()";
                        $this->passosSequencia($fqcn, $passo['metodo'], $idAtual, $profundidade + 1, $visitados + [$chave => true], $linhas, $participantes, $ids);
                        $linhas[] = "{$idAtual}-->>-{$idAtual}: ";
                    } else {
                        $linhas[] = "{$idAtual}->>{$idAtual}: {$passo['metodo']}()";
                    }
                    break;

                case 'estatica':
                case 'instancia':
                    $idAlvo = $this->idParticipante($this->curto($passo['classe']), $participantes, $ids);
                    $linhas[] = "{$idAtual}->>+{$idAlvo}: {$passo['metodo']}()";
                    $linhas[] = "{$idAlvo}-->>-{$idAtual}: ";
                    break;

                case 'new':
                    $idAlvo = $this->idParticipante($this->curto($passo['classe']), $participantes, $ids);
                    $linhas[] = "{$idAtual}->>+{$idAlvo}: new()";
                    $linhas[] = "{$idAlvo}-->>-{$idAtual}: ";
                    break;

                case 'job':
                    $idAlvo = $this->idParticipante($this->curto($passo['classe']), $participantes, $ids);
                    $linhas[] = "{$idAtual}->>{$idAlvo}: dispatch()";
                    $linhas[] = "Note right of {$idAlvo}: assíncrono (fila)";
                    break;

                case 'dispatch_evento':
                    $linhas[] = "Note over {$idAtual}: dispara evento \"" . $this->escaparMermaid($passo['evento']) . '"';
                    break;

                case 'evento':
                    $linhas[] = "Note over {$idAtual}: evento \"" . $this->escaparMermaid($passo['evento']) . '"';
                    break;

                case 'view':
                    $linhas[] = "Note over {$idAtual}: renderiza view \"" . $this->escaparMermaid($passo['view']) . '"';
                    break;

                case 'validacao':
                    $linhas[] = "Note over {$idAtual}: valida dados";
                    break;

                case 'resposta':
                    $linhas[] = "Note over {$idAtual}: {$passo['nome']}()";
                    break;
            }
        }
    }

    private function buscarMetodo(string $fqcn, string $metodo): ?array
    {
        $class = $this->index->classes[$fqcn] ?? null;
        if ($class === null) {
            return null;
        }
        foreach ($class['metodos'] as $m) {
            if (strcasecmp($m['nome'], $metodo) === 0) {
                return $m;
            }
        }
        foreach (array_merge($class['traits'], $class['extends']) as $pai) {
            if (isset($this->index->classes[$pai])) {
                $achado = $this->buscarMetodo($pai, $metodo);
                if ($achado !== null) {
                    return $achado;
                }
            }
        }

        return null;
    }

    private function idParticipante(string $label, array &$participantes, array &$ids): string
    {
        if (isset($ids[$label])) {
            return $ids[$label];
        }
        $id = 'P' . (count($ids) + 1);
        $ids[$label] = $id;
        $participantes[$id] = $this->escaparMermaid($label);

        return $id;
    }

    private function escaparMermaid(string $s): string
    {
        return str_replace(['"', "\n"], ["'", ' '], $s);
    }

    private function fluxoPorArquivo(): string
    {
        $grupos = [];
        foreach ($this->index->classes as $fqcn => $class) {
            $grupos[$class['categoria'] ?? 'classe'][$fqcn] = $class;
        }

        $ordem = array_keys(self::ROTULOS);
        uksort($grupos, function ($a, $b) use ($ordem) {
            $pa = array_search($a, $ordem, true);
            $pb = array_search($b, $ordem, true);

            return ($pa === false ? 999 : $pa) <=> ($pb === false ? 999 : $pb);
        });

        $out = ["## Fluxo por arquivo\n"];

        foreach ($grupos as $categoria => $classes) {
            $out[] = '### ' . (self::ROTULOS[$categoria] ?? $categoria) . "\n";
            ksort($classes);
            foreach ($classes as $fqcn => $class) {
                $out[] = $this->renderClasse($fqcn, $class);
            }
        }

        return implode("\n", $out) . "\n---\n";
    }

    private function renderClasse(string $fqcn, array $class): string
    {
        $linhas = [];
        $voltTag = isset($class['volt']) ? ' · **Volt** (' . $class['volt'] . ')' : '';
        $linhas[] = "#### `{$fqcn}`{$voltTag}";
        $linhas[] = "`{$class['arquivo']}` · linha {$class['linha']}\n";

        $meta = [];
        if ($class['extends'] !== []) {
            $meta[] = '**Estende:** ' . $this->listaCodigo($class['extends']);
        }
        if ($class['implements'] !== []) {
            $meta[] = '**Implementa:** ' . $this->listaCodigo($class['implements']);
        }
        if ($class['traits'] !== []) {
            $meta[] = '**Traits:** ' . $this->listaCodigo($class['traits']);
        }
        if ($class['atributos'] !== []) {
            $meta[] = '**Atributos PHP:** ' . implode(', ', array_map(
                fn ($a) => '`#[' . $a['nome'] . ($a['arg'] !== null ? "('" . $a['arg'] . "')" : '') . ']`',
                $class['atributos']
            ));
        }

        $propsTipadas = [];
        foreach ($class['propriedades'] as $nome => $prop) {
            if ($prop['tipo'] !== null && ! in_array(strtolower($prop['tipo']), ['int', 'string', 'bool', 'float', 'array', 'mixed', 'object', 'callable', 'iterable'], true)) {
                $propsTipadas[] = '`$' . $nome . '`: `' . $this->curto($prop['tipo']) . '`';
            }
        }
        if ($propsTipadas !== []) {
            $meta[] = '**Propriedades tipadas:** ' . implode(', ', $propsTipadas);
        }

        $modelsClasse = $this->modelsDaClasse($class);
        if ($modelsClasse !== []) {
            $meta[] = '**Models usados na classe:** ' . $this->listaCodigo($modelsClasse);
        }

        $views = [];
        foreach ($class['metodos'] as $m) {
            $views = array_merge($views, $m['refs']['views']);
        }
        foreach ($class['atributos'] as $a) {
            if (strtolower($a['nome']) === 'layout' && $a['arg'] !== null) {
                $views[] = $a['arg'] . ' (layout)';
            }
        }
        if ($views !== []) {
            $meta[] = '**Views:** ' . $this->listaCodigo(array_unique($views));
        }

        if ($meta !== []) {
            $linhas[] = implode("\n", array_map(fn ($m) => '- ' . $m, $meta)) . "\n";
        }

        if ($class['metodos'] === []) {
            $linhas[] = "_Sem métodos._\n";

            return implode("\n", $linhas);
        }

        foreach ($class['metodos'] as $metodo) {
            $linhas[] = $this->renderMetodo($fqcn, $metodo);
        }

        return implode("\n", $linhas);
    }

    private function renderMetodo(string $fqcn, array $metodo): string
    {
        $params = implode(', ', array_map(
            fn ($p) => (($p['nullable'] ?? false) ? '?' : '') . ($p['tipo'] !== null ? $this->curto($p['tipo']) . ' ' : '') . $p['nome'],
            $metodo['parametros']
        ));
        $mods = array_diff($metodo['modificadores'], ['public']);
        $prefixo = $mods !== [] ? implode(' ', $mods) . ' ' : '';

        $linhas = [];
        $linhas[] = "##### `{$prefixo}{$metodo['nome']}({$params})` — linha {$metodo['linha']}";

        $refs = $metodo['refs'];
        $itens = [];

        if (! empty($metodo['atributos'])) {
            $itens[] = '**Atributos:** ' . implode(', ', array_map(
                fn ($a) => '`#[' . $a['nome'] . ($a['arg'] !== null ? "('" . $a['arg'] . "')" : '') . ']`',
                $metodo['atributos']
            ));
        }

        $chamadores = $this->index->callersOf($fqcn, $metodo['nome']);
        if ($chamadores !== []) {
            $itens[] = '**Acionado por:** ' . implode(', ', array_map(
                fn ($c) => str_contains($c, '`') ? $c : '`' . $this->curto($c) . '()`',
                $chamadores
            ));
        }

        $models = [];
        foreach (['chamadas_estaticas', 'chamadas_instancia'] as $bucket) {
            foreach ($refs[$bucket] as $classe => $metodos) {
                if ($this->index->isModel($classe)) {
                    $models[] = '`' . $this->curto($classe) . '` → ' . implode(', ', array_map(fn ($m) => "`{$m}()`", $metodos));
                }
            }
        }
        foreach ($refs['instanciacoes'] as $classe => $_) {
            if ($this->index->isModel($classe)) {
                $models[] = '`' . $this->curto($classe) . '` → `new`';
            }
        }
        foreach ($refs['propriedades'] as $prop => $tipo) {
            if ($this->index->isModel($tipo)) {
                $models[] = '`' . $this->curto($tipo) . '` (via `$this->' . $prop . '`)';
            }
        }
        if ($models !== []) {
            $itens[] = '**Models:** ' . implode(' · ', array_unique($models));
        }

        $internas = [];
        foreach ($refs['chamadas_internas_detalhe'] ?? [] as $chamada) {
            if ($chamada['existe']) {
                $internas[] = '`$this->' . $chamada['metodo'] . '()`';
            } elseif ($chamada['framework']) {
                $internas[] = '`$this->' . $chamada['metodo'] . '()` _(Livewire/framework)_';
            } else {
                $internas[] = '`$this->' . $chamada['metodo'] . '()` _(herdado/trait)_';
            }
        }
        if ($internas !== []) {
            $itens[] = '**Chama nesta classe:** ' . implode(', ', $internas);
        }

        $externas = [];
        foreach (['chamadas_estaticas' => '::', 'chamadas_instancia' => '->'] as $bucket => $sep) {
            foreach ($refs[$bucket] as $classe => $metodos) {
                if ($this->index->isModel($classe) || $classe === $fqcn) {
                    continue;
                }
                $externas[] = '`' . $this->curto($classe) . $sep . implode('/', $metodos) . '()`' . $this->tagClasse($classe);
            }
        }
        if ($externas !== []) {
            $itens[] = '**Chama em outras classes:** ' . implode(', ', $externas);
        }

        $novos = [];
        foreach ($refs['instanciacoes'] as $classe => $_) {
            if (! $this->index->isModel($classe)) {
                $novos[] = '`new ' . $this->curto($classe) . '`' . $this->tagClasse($classe);
            }
        }
        if ($novos !== []) {
            $itens[] = '**Instancia:** ' . implode(', ', $novos);
        }

        if (($refs['relacionamentos'] ?? []) !== []) {
            $itens[] = '**Relacionamento:** ' . implode(', ', array_map(
                fn ($r) => '`' . $r['tipo'] . '` → `' . $this->curto($r['classe']) . '`',
                $refs['relacionamentos']
            ));
        }
        if ($refs['jobs'] !== []) {
            $itens[] = '**Jobs despachados:** ' . implode(', ', array_map(
                fn ($job) => '`' . $this->curto($job) . '(' . $this->paramsDoGatilho($refs['gatilhos'] ?? [], 'Job dispatch', $job) . ')`',
                array_unique($refs['jobs'])
            ));
        }
        if ($refs['eventos_livewire'] !== []) {
            $itens[] = '**Eventos Livewire emitidos:** ' . implode(', ', array_map(
                fn ($ev) => '`' . $ev . $this->parenteses($this->paramsDoGatilho($refs['gatilhos'] ?? [], 'Livewire dispatch', $ev)) . '`',
                array_unique($refs['eventos_livewire'])
            ));
        }
        if ($refs['eventos'] !== []) {
            $itens[] = '**Eventos disparados:** ' . implode(', ', array_map(
                fn ($ev) => '`' . $ev . $this->parenteses($this->paramsDoGatilho($refs['gatilhos'] ?? [], 'Event', $ev)) . '`',
                array_unique($refs['eventos'])
            ));
        }
        if ($refs['views'] !== []) {
            $itens[] = '**Views renderizadas:** ' . implode(', ', array_map(
                fn ($v) => '`' . $v . '`' . (isset($this->index->views[$v]) ? ' → `' . $this->index->views[$v] . '`' : ' ⚠️ _não encontrada_'),
                $refs['views']
            ));
        }
        if ($refs['rotas'] !== []) {
            $itens[] = '**Rotas referenciadas:** ' . $this->listaCodigo($refs['rotas']);
        }
        if ($refs['permissoes'] !== []) {
            $itens[] = '**Permissões / feature flags:** ' . $this->listaCodigo($refs['permissoes']);
        }
        if ($refs['valida']) {
            $itens[] = '**Validação:** executa `validate()`';
        }
        $helpers = array_keys($refs['helpers']);
        if ($helpers !== []) {
            $itens[] = '**Helpers:** ' . implode(', ', array_map(fn ($h) => "`{$h}()`", $helpers));
        }
        if ($refs['funcoes_globais'] !== []) {
            $itens[] = '**Funções:** ' . $this->listaCodigo(array_slice($refs['funcoes_globais'], 0, 15));
        }

        if ($itens === []) {
            $itens[] = '_Sem dependências externas detectadas._';
        }

        $linhas[] = implode("\n", array_map(fn ($i) => '- ' . $i, $itens)) . "\n";

        return implode("\n", $linhas);
    }

    private function viewsBlade(): string
    {
        $out = ["## Views Blade\n"];

        if ($this->index->blades === []) {
            return $out[0] . "_Nenhuma view própria encontrada._\n\n---\n";
        }

        $blades = $this->index->blades;
        ksort($blades);

        foreach ($blades as $rel => $blade) {
            $out[] = "### `{$blade['view']}`";
            $out[] = "`{$rel}`\n";

            $itens = [];
            $owner = $this->index->viewOwner[$blade['view']] ?? null;
            if ($owner !== null) {
                $itens[] = '**Renderizada por:** `' . $owner . '`';
            }
            if ($blade['extends'] !== null) {
                $itens[] = '**Estende layout:** `' . $blade['extends'] . '`';
            }
            if ($blade['includes'] !== []) {
                $itens[] = '**Inclui:** ' . $this->listaCodigo($blade['includes']);
            }
            if ($blade['componentes'] !== []) {
                $itens[] = '**Componentes Blade:** ' . implode(', ', array_map(function ($c) {
                    $alvo = $this->index->views['components.' . str_replace(':', '.', $c)] ?? null;

                    return '`<x-' . $c . '>`' . ($alvo !== null ? ' → `' . $alvo . '`' : '');
                }, $blade['componentes']));
            }
            if ($blade['livewire'] !== []) {
                $itens[] = '**Componentes Livewire:** ' . implode(', ', array_map(function ($nome) {
                    $fqcn = $this->index->componentFromAlias($nome);

                    return '`' . $nome . '`' . ($fqcn !== null ? ' → `' . $this->curto($fqcn) . '`' : ' ⚠️ _classe não localizada_');
                }, $blade['livewire']));
            }

            if ($blade['acoes'] !== []) {
                $acoes = [];
                foreach ($blade['acoes'] as $acao) {
                    if ($acao['metodo'] === null) {
                        continue;
                    }
                    $destino = $owner !== null && $this->index->methodExists($owner, $acao['metodo'])
                        ? '`' . $this->curto($owner) . '::' . $acao['metodo'] . '()`'
                        : '`' . $acao['metodo'] . '()` ⚠️ _não localizado no componente_';
                    $acoes[] = '`' . $acao['evento'] . '` → ' . $destino;
                }
                if ($acoes !== []) {
                    $itens[] = "**Ações que aciona:**\n" . implode("\n", array_map(fn ($a) => '  - ' . $a, array_unique($acoes)));
                }
            }

            if ($blade['wire_js'] !== []) {
                $itens[] = '**Chamadas via `$wire`:** ' . $this->listaCodigo($blade['wire_js']);
            }
            if ($blade['models_wire'] !== []) {
                $itens[] = '**Propriedades ligadas (`wire:model`):** ' . $this->listaCodigo(array_slice($blade['models_wire'], 0, 25));
            }
            if ($blade['rotas'] !== []) {
                $itens[] = '**Rotas usadas:** ' . $this->listaCodigo($blade['rotas']);
            }
            if ($blade['permissoes'] !== []) {
                $itens[] = '**Permissões checadas:** ' . $this->listaCodigo($blade['permissoes']);
            }
            if ($blade['classes_php'] !== []) {
                $itens[] = '**Classes referenciadas:** ' . $this->listaCodigo($blade['classes_php']);
            }
            if ($blade['secoes'] !== []) {
                $itens[] = '**Seções:** ' . $this->listaCodigo($blade['secoes']);
            }

            $out[] = $itens === []
                ? "_View estática._\n"
                : implode("\n", array_map(fn ($i) => '- ' . $i, $itens)) . "\n";

            if ($blade['elementos'] !== []) {
                $out[] = $this->tabelaElementos($blade['elementos'], $owner);
            }
        }

        return implode("\n", $out) . "\n---\n";
    }

    /** Tabela de <button> e <a> encontrados na view, com a ação que cada um dispara. */
    private function tabelaElementos(array $elementos, ?string $owner): string
    {
        $out = ['**Botões e links:**', '', '| Tipo | Texto | Aciona | Linha |', '| --- | --- | --- | --- |'];

        foreach ($elementos as $el) {
            $aciona = $el['acao'];
            if ($el['tipo_acao'] === 'metodo') {
                $existe = $owner !== null && $this->index->methodExists($owner, $el['acao']);
                $aciona = $existe
                    ? '`' . $this->curto((string) $owner) . '::' . $el['acao'] . '()`'
                    : '`' . $el['acao'] . '()` ⚠️ _não localizado no componente_';
            } elseif ($el['tipo_acao'] === 'rota') {
                $aciona = 'rota `' . $el['acao'] . '`';
            } elseif ($el['tipo_acao'] === 'js') {
                $aciona = 'JS: `' . $this->truncar($el['acao'], 60) . '`';
            } elseif ($el['tipo_acao'] === 'href') {
                $aciona = '`' . $this->truncar($el['acao'], 60) . '`';
            } else {
                $aciona = '_' . $el['acao'] . '_';
            }

            $texto = $el['texto'] === '' ? '_(sem texto — ícone?)_' : $this->truncar(str_replace('|', '\\|', $el['texto']), 40);

            $out[] = sprintf('| %s | %s | %s | %d |', $el['tipo'], $texto, str_replace('|', '\\|', $aciona), $el['linha']);
        }

        return implode("\n", $out) . "\n";
    }

    private function indiceReverso(): string
    {
        $out = ["## Índice reverso — quem chama quem\n"];
        $callers = $this->index->callers;
        ksort($callers);

        $out[] = '| Método | Acionado por |';
        $out[] = '| --- | --- |';

        foreach ($callers as $alvo => $origens) {
            $origens = array_unique($origens);
            $out[] = '| `' . $this->curto($alvo) . '()` | ' . implode(', ', array_map(
                fn ($o) => str_contains($o, '`') ? $o : '`' . $this->curto($o) . '()`',
                $origens
            )) . ' |';
        }

        return implode("\n", $out) . "\n\n---\n";
    }

    private function modelsUsados(): string
    {
        $out = ["## Models e onde são usados\n"];
        $usage = $this->index->modelUsage;

        if ($usage === []) {
            return $out[0] . "_Nenhum uso de model detectado._\n\n---\n";
        }

        ksort($usage);
        foreach ($usage as $model => $origens) {
            $origens = array_unique($origens);
            $out[] = "### `{$model}`";
            $out[] = implode("\n", array_map(fn ($o) => '- `' . $this->curto($o) . '`', $origens)) . "\n";
        }

        return implode("\n", $out) . "\n---\n";
    }

    /** Diagrama entidade-relacionamento (Mermaid erDiagram) a partir das migrations. */
    private function diagramaBanco(): string
    {
        $out = ["## Diagrama de banco de dados\n"];
        $tabelas = $this->index->tabelas;

        if ($tabelas === []) {
            return $out[0] . "_Nenhuma tabela encontrada nas migrations (ou o projeto usa `Schema::create` de um jeito não reconhecido)._\n\n---\n";
        }

        ksort($tabelas);

        $relacoes = [];
        foreach ($tabelas as $nome => $info) {
            foreach ($info['colunas'] as $col) {
                $fk = $col['fk'] ?? null;
                if ($fk === null || ! isset($tabelas[$fk['tabela']])) {
                    continue;
                }
                $rotulo = (string) preg_replace('/_id$/', '', $col['nome']);
                $cardinalidade = ($col['unique'] ?? false) ? '||--||' : '||--o{';
                $relacoes[] = sprintf(
                    '    %s %s %s : "%s"',
                    $this->idMermaid($fk['tabela']),
                    $cardinalidade,
                    $this->idMermaid($nome),
                    $rotulo !== '' ? $rotulo : 'relaciona'
                );
            }
        }

        $out[] = "```mermaid\nerDiagram";
        foreach (array_values(array_unique($relacoes)) as $l) {
            $out[] = $l;
        }
        foreach ($tabelas as $nome => $info) {
            $out[] = '    ' . $this->idMermaid($nome) . ' {';
            foreach ($info['colunas'] as $col) {
                $flags = trim(($col['pk'] ? 'PK ' : '') . (($col['fk'] ?? null) !== null ? 'FK ' : '') . ($col['unique'] ? 'UK' : ''));
                $out[] = sprintf('        %s %s%s', $this->idMermaid($col['tipo']), $this->idMermaid($col['nome']), $flags !== '' ? ' ' . $flags : '');
            }
            $out[] = '    }';
        }
        $out[] = '```';

        foreach ($tabelas as $nome => $info) {
            $out[] = "\n### `{$nome}`";
            $origens = array_unique($info['origem'] ?? []);
            if ($origens !== []) {
                $out[] = '_Definida em: ' . implode(', ', array_map(fn ($o) => '`' . $o . '`', $origens)) . "_\n";
            }
            $out[] = '| Coluna | Tipo | Nulo? | Único? | Chave estrangeira |';
            $out[] = '| --- | --- | --- | --- | --- |';
            foreach ($info['colunas'] as $col) {
                $fk = $col['fk'] !== null ? '`' . $col['fk']['tabela'] . '.' . $col['fk']['coluna'] . '`' : '—';
                $out[] = sprintf(
                    '| `%s`%s | `%s` | %s | %s | %s |',
                    $col['nome'],
                    $col['pk'] ? ' 🔑' : '',
                    $col['tipo'],
                    $col['nullable'] ? 'sim' : 'não',
                    $col['unique'] ? 'sim' : 'não',
                    $fk
                );
            }
        }

        return implode("\n", $out) . "\n\n---\n";
    }

    private function idMermaid(string $nome): string
    {
        return (string) preg_replace('/[^a-zA-Z0-9_]/', '_', $nome);
    }

    private function eventosLivewire(): string
    {
        $out = ["## Eventos\n"];
        if ($this->index->eventos === []) {
            return $out[0] . "_Nenhum evento detectado._\n\n---\n";
        }

        $out[] = '| Evento | Emitido por | Ouvido por |';
        $out[] = '| --- | --- | --- |';
        $eventos = $this->index->eventos;
        ksort($eventos);

        foreach ($eventos as $evento => $dados) {
            $emissores = array_unique($dados['emissores'] ?? []);
            $ouvintes = array_unique($dados['ouvintes'] ?? []);
            $out[] = '| `' . $evento . '` | '
                . ($emissores === [] ? '—' : implode(', ', array_map(fn ($e) => '`' . $this->curto($e) . '()`', $emissores)))
                . ' | '
                . ($ouvintes === [] ? '⚠️ _nenhum ouvinte encontrado_' : implode(', ', array_map(fn ($e) => '`' . $this->curto($e) . '()`', $ouvintes)))
                . ' |';
        }

        return implode("\n", $out) . "\n\n---\n";
    }

    /** Tabela consolidada de todo dispatch/evento/job disparado no projeto, com parâmetros. */
    private function gatilhos(): string
    {
        $out = ["## Gatilhos (dispatch / disparar)\n"];

        if ($this->index->gatilhos === []) {
            return $out[0] . "_Nenhum gatilho encontrado._\n\n---\n";
        }

        $out[] = '| Tipo | Origem | Alvo | Parâmetros | Local |';
        $out[] = '| --- | --- | --- | --- | --- |';

        foreach ($this->index->gatilhos as $g) {
            $params = $g['params'] === []
                ? '—'
                : implode('; ', array_map(fn ($p) => '`' . str_replace('|', '\\|', $this->truncar($p, 50)) . '`', $g['params']));

            $out[] = sprintf(
                '| %s | `%s` | `%s` | %s | `%s:%d` |',
                $g['tipo'],
                $this->curto($g['origem']),
                str_replace('|', '\\|', $this->curto($g['alvo'])),
                $params,
                $g['arquivo'],
                $g['linha']
            );
        }

        return implode("\n", $out) . "\n\n---\n";
    }

    private function orfaos(): string
    {
        $out = ["## Métodos sem chamador conhecido\n"];
        $out[] = "_Candidatos a código morto. Confira antes de remover: podem ser acionados por rotas dinâmicas, Blade gerado em runtime, comandos ou pacotes._\n";

        $linhas = [];
        foreach ($this->index->classes as $fqcn => $class) {
            foreach ($class['metodos'] as $metodo) {
                if ($this->index->isOrphan($fqcn, $metodo)) {
                    $linhas[] = '- `' . $fqcn . '::' . $metodo['nome'] . '()` — `' . $class['arquivo'] . ':' . $metodo['linha'] . '`';
                }
            }
        }

        $out[] = $linhas === [] ? '_Nenhum._' : implode("\n", $linhas);

        return implode("\n", $out) . "\n\n---\n";
    }

    private function grafo(): string
    {
        $edges = [];
        foreach ($this->index->edges as [$origem, $destino, $tipo]) {
            if ($origem === $destino) {
                continue;
            }
            $key = $origem . '|' . $destino;
            $edges[$key] ??= [$origem, $destino, $tipo];
        }

        if ($edges === []) {
            return '';
        }

        $out = ["## Grafo de dependências\n", '```mermaid', 'graph LR'];
        $ids = [];
        $seq = 0;
        $limite = 250;
        $n = 0;

        foreach ($edges as [$origem, $destino, $tipo]) {
            if ($n++ > $limite) {
                break;
            }
            foreach ([$origem, $destino] as $node) {
                if (! isset($ids[$node])) {
                    $ids[$node] = 'n' . (++$seq);
                    $label = str_starts_with($node, 'view:') || str_starts_with($node, 'rota:')
                        ? $node
                        : $this->curto($node);
                    $forma = str_starts_with($node, 'rota:') ? '(["%s"])' : (str_starts_with($node, 'view:') ? '[/"%s"/]' : '["%s"]');
                    $out[] = '    ' . $ids[$node] . sprintf($forma, str_replace('"', "'", $label));
                }
            }
            $seta = match ($tipo) {
                'relacao' => '-. relação .->',
                'job' => '-. job .->',
                'rota' => '==>',
                'wire' => '-. wire .->',
                'render' => '-. render .->',
                default => '-->',
            };
            $out[] = '    ' . $ids[$origem] . ' ' . $seta . ' ' . $ids[$destino];
        }

        $out[] = '```';

        return implode("\n", $out) . "\n\n---\n";
    }

    /** Árvore de diretórios com apenas os arquivos próprios (mapeados) do projeto. */
    private function arvoreArquitetura(): string
    {
        $out = ["## Árvore da arquitetura\n"];
        $out[] = "_Somente os arquivos próprios do projeto — o que foi de fato mapeado. Arquivos padrão da stack estão na seção final._\n";

        if ($this->scan['relevantes'] === []) {
            return implode("\n", $out) . "\n_Nenhum arquivo próprio encontrado._\n\n---\n";
        }

        $arvore = [];
        foreach ($this->scan['relevantes'] as $rel) {
            $partes = explode('/', $rel);
            $ref = &$arvore;
            foreach ($partes as $parte) {
                $ref[$parte] ??= [];
                $ref = &$ref[$parte];
            }
            unset($ref);
        }

        $out[] = "```\n" . $this->projeto . '/' . "\n" . $this->renderArvore($arvore) . '```';

        return implode("\n", $out) . "\n\n---\n";
    }

    private function renderArvore(array $nivel, string $prefixo = ''): string
    {
        $chaves = array_keys($nivel);
        usort($chaves, function ($a, $b) use ($nivel) {
            $dirA = $nivel[$a] !== [];
            $dirB = $nivel[$b] !== [];

            return $dirA !== $dirB ? ($dirB <=> $dirA) : strcasecmp($a, $b);
        });

        $out = '';
        $total = count($chaves);

        foreach ($chaves as $idx => $nome) {
            $ultimo = $idx === $total - 1;
            $ramo = $ultimo ? '└── ' : '├── ';
            $isDir = $nivel[$nome] !== [];

            $out .= $prefixo . $ramo . $nome . ($isDir ? '/' : '') . "\n";

            if ($isDir) {
                $out .= $this->renderArvore($nivel[$nome], $prefixo . ($ultimo ? '    ' : '│   '));
            }
        }

        return $out;
    }

    /** Lista separada de permissões/roles e feature flags encontradas no código. */
    private function permissoesFeatures(): string
    {
        $permissoes = [];
        $features = [];

        foreach ($this->index->classes as $fqcn => $class) {
            foreach ($class['metodos'] as $metodo) {
                $origem = $fqcn . '::' . $metodo['nome'];
                foreach ($metodo['refs']['permissoes'] as $p) {
                    if (! str_starts_with($p, 'feature:')) { // features vêm do FeatureScanner (tabelaFeatures)
                        $permissoes[$p][] = $origem;
                    }
                }
            }
        }
        foreach ($this->index->blades as $blade) {
            foreach ($blade['permissoes'] as $p) {
                $permissoes[$p][] = 'view `' . $blade['view'] . '`';
            }
        }

        ksort($permissoes);

        $out = ["## Permissões e Feature Flags\n"];
        $out[] = $this->tabelaPermissoes('Permissões / Roles (`can`, `hasRole`, `@can`...)', $permissoes);
        $out[] = $this->tabelaFeatures();

        return implode("\n", $out) . "\n---\n";
    }

    /** Todas as verificações de feature: nome, quantas vezes, de que forma e onde. */
    private function tabelaFeatures(): string
    {
        $out = ["### Feature Flags (`@feature`, `feature()`, `@if(feature(...))`, `Feature::active()`, middleware)\n"];
        $lista = $this->index->features;
        if ($lista === []) {
            return implode("\n", $out) . "_Nenhuma verificação de feature encontrada._\n";
        }

        // contexto de cada ocorrência: Classe::metodo() (PHP) ou a view (Blade)
        $metodos = [];
        foreach ($this->index->classes as $fqcn => $class) {
            foreach ($class['metodos'] as $m) {
                $metodos[$class['arquivo']][] = [$m['linha'], $this->curto($fqcn) . '::' . $m['nome'] . '()'];
            }
        }
        foreach ($metodos as &$l) {
            usort($l, fn (array $a, array $b): int => $a[0] <=> $b[0]);
        }
        unset($l);
        $contexto = function (array $f) use ($metodos): string {
            $rel = $f['arquivo'];
            if (str_ends_with($rel, '.blade.php')) {
                return 'view `' . BladeAnalyzer::viewName($rel) . '`';
            }
            $melhor = null;
            foreach ($metodos[$rel] ?? [] as [$ln, $rot]) {
                if ($ln > $f['linha']) {
                    break;
                }
                $melhor = $rot;
            }
            if ($melhor !== null) {
                return '`' . $melhor . '`';
            }

            return str_starts_with($rel, 'routes/') ? 'rota' : '(nível do arquivo)';
        };

        $nomes = [];
        $dinamicas = [];
        $porForma = [];
        $arquivos = [];
        foreach ($lista as $f) {
            $porForma[$f['forma']] = ($porForma[$f['forma']] ?? 0) + 1;
            $arquivos[$f['arquivo']] = true;
            $local = $contexto($f) . ' — `' . $f['arquivo'] . ':' . $f['linha'] . '`';
            if ($f['dinamico']) {
                $dinamicas[] = [$f, $local];
                continue;
            }
            $nomes[$f['nome']]['qtd'] = ($nomes[$f['nome']]['qtd'] ?? 0) + 1;
            $nomes[$f['nome']]['formas'][$f['forma']] = true;
            $nomes[$f['nome']]['locais'][] = $local;
        }
        ksort($nomes);
        arsort($porForma);

        $estaticas = array_sum(array_column($nomes, 'qtd'));
        $out[] = '_' . count($nomes) . ' features distintas · ' . $estaticas . ' verificações · ' . count($arquivos) . ' arquivos'
            . (count($dinamicas) > 0 ? ' · ' . count($dinamicas) . ' verificações com nome dinâmico (tabela abaixo)' : '') . '. '
            . 'Por forma: ' . implode(', ', array_map(fn ($f, $n) => '`' . $f . '` ×' . $n, array_keys($porForma), $porForma)) . '. '
            . "Cada verificação conta uma vez, em PHP ou em view._\n";

        $out[] = '| Feature | Qtd | Formas | Verificada em |';
        $out[] = '| --- | ---: | --- | --- |';
        foreach ($nomes as $nome => $d) {
            $locais = $d['locais'];
            $extra = count($locais) > 15 ? '<br>…+' . (count($locais) - 15) . ' (lista completa no `--json`)' : '';
            $out[] = '| `' . str_replace('|', '\\|', (string) $nome) . '` | ' . $d['qtd'] . ' | '
                . implode(', ', array_map(fn ($f) => '`' . $f . '`', array_keys($d['formas']))) . ' | '
                . implode('<br>', array_slice($locais, 0, 15)) . $extra . ' |';
        }

        if ($dinamicas !== []) {
            $out[] = "\n#### Verificações com nome dinâmico\n";
            $out[] = '_O nome da feature não é um texto fixo (variável ou concatenação), então não dá para saber qual é só lendo o código. Confira cada uma._' . "\n";
            $out[] = '| Local | Forma | Expressão |';
            $out[] = '| --- | --- | --- |';
            foreach ($dinamicas as [$f, $local]) {
                $out[] = '| ' . $local . ' | `' . $f['forma'] . '` | `' . str_replace('|', '\\|', $f['nome']) . '` |';
            }
        }

        return implode("\n", $out) . "\n";
    }

    private function tabelaPermissoes(string $titulo, array $mapa): string
    {
        $out = ["### {$titulo}\n"];
        if ($mapa === []) {
            return implode("\n", $out) . "_Nenhuma encontrada._\n";
        }

        $out[] = '| Nome | Verificada em |';
        $out[] = '| --- | --- |';
        foreach ($mapa as $nome => $origens) {
            $origens = array_unique($origens);
            $out[] = '| `' . $nome . '` | ' . implode(', ', array_map(
                fn ($o) => str_contains($o, '`') ? $o : '`' . $this->curto($o) . '()`',
                $origens
            )) . ' |';
        }

        return implode("\n", $out) . "\n";
    }

    /** Lista simples de todas as funções/métodos, agrupadas por arquivo. */
    private function indiceFuncoes(): string
    {
        $porArquivo = [];

        foreach ($this->index->classes as $fqcn => $class) {
            foreach ($class['metodos'] as $metodo) {
                $porArquivo[$class['arquivo']][] = [
                    'assinatura' => $this->curto($fqcn) . '::' . $metodo['nome'] . '()',
                    'linha' => $metodo['linha'],
                ];
            }
        }
        foreach ($this->index->arquivos as $rel => $file) {
            if (($file['tipo'] ?? '') === 'php') {
                foreach ($file['funcoes'] ?? [] as $fn) {
                    $porArquivo[$rel][] = ['assinatura' => $fn['nome'] . '()', 'linha' => $fn['linha']];
                }
            }
        }

        $out = ["## Índice de funções por arquivo\n"];
        if ($porArquivo === []) {
            return implode("\n", $out) . "_Nenhuma função encontrada._\n\n---\n";
        }

        ksort($porArquivo);
        foreach ($porArquivo as $rel => $funcoes) {
            usort($funcoes, fn ($a, $b) => $a['linha'] <=> $b['linha']);
            $out[] = "**`{$rel}`**";
            $out[] = implode("\n", array_map(fn ($f) => "- `{$f['assinatura']}` — linha {$f['linha']}", $funcoes)) . "\n";
        }

        return implode("\n", $out) . "\n---\n";
    }

    /** Seção com todos os textos do sistema: onde aparecem (diretório) e quantas vezes. */
    private function textosDoSistema(): string
    {
        if ($this->textosArquivo !== null) {
            return "## Textos e strings do sistema\n\n_Gerado em arquivo separado: `" . basename($this->textosArquivo) . "` (opção `--strings-out`)._\n\n---\n";
        }

        return $this->corpoTextos() . "\n---\n";
    }

    /** Documento só com os textos (usado por --strings-out). */
    public function renderTextosAvulso(): string
    {
        $data = date('d/m/Y H:i');

        return "# 🔤 Textos e strings — {$this->projeto}\n\n> Gerado por **LaraMap** em {$data}\n\n" . $this->corpoTextos();
    }

    private function corpoTextos(): string
    {
        $textos = $this->index->textos;
        $out = ["## Textos e strings do sistema\n"];

        if ($textos === []) {
            return $out[0] . "_Nenhum texto encontrado._\n";
        }

        $rotulos = ['view' => 'view', 'atributo' => 'atributo', 'expressao' => 'expressão', 'traducao' => 'tradução', 'php' => 'php', 'js' => 'js', 'lang' => 'lang'];
        $descricoes = [
            'view' => 'texto visível em view Blade/HTML',
            'atributo' => '`placeholder`, `title`, `alt`, `aria-label`, `wire:confirm`, props de componentes, expressões Alpine',
            'expressao' => 'string dentro de `{{ ... }}` ou de diretiva (`@section`, `@include`...)',
            'traducao' => '`__()`, `trans()`, `@lang()`',
            'php' => 'string em PHP (mensagens, validações, seeders, e-mails em HTML/heredoc)',
            'js' => 'string em JavaScript / `<script>`',
            'lang' => 'arquivos `lang/`',
        ];

        $totalOcorr = 0;
        $arquivos = [];
        $porTipo = [];
        $porDir = [];

        foreach ($textos as $texto => $e) {
            $totalOcorr += $e['total'];
            foreach ($e['tipos'] as $tp => $n) {
                $porTipo[$tp] = ($porTipo[$tp] ?? 0) + $n;
            }
            $dirsDoTexto = [];
            foreach ($e['arquivos'] as $rel => $info) {
                $arquivos[$rel] = true;
                $dir = $this->diretorioDe((string) $rel);
                $porDir[$dir]['total'] = ($porDir[$dir]['total'] ?? 0) + $info['n'];
                $dirsDoTexto[$dir] = true;
            }
            foreach (array_keys($dirsDoTexto) as $dir) {
                $porDir[$dir]['unicos'] = ($porDir[$dir]['unicos'] ?? 0) + 1;
            }
        }

        $out[] = '_' . count($textos) . ' textos únicos · ' . $totalOcorr . ' ocorrências · ' . count($arquivos) . ' arquivos. '
            . 'Cada linha é uma string exata — palavra solta ou frase inteira. Tags de personalização ficam no lugar, dentro da frase '
            . '(`{{ $nome }}`, `{{nome}}`, `{nome}`, `{$nome}`). Em views, `<strong>`/`<b>`/`<em>` não quebram a frase. '
            . "Por padrão só entram textos de interface (chaves, classes CSS, caminhos, SQL e formatos de data são descartados); use `--strings-todas` para listar tudo._\n";

        arsort($porTipo);
        $out[] = '### Por tipo de origem';
        $out[] = '';
        $out[] = '| Tipo | Ocorrências | O que é |';
        $out[] = '| --- | ---: | --- |';
        foreach ($porTipo as $tp => $n) {
            $out[] = '| ' . ($rotulos[$tp] ?? $tp) . ' | ' . $n . ' | ' . ($descricoes[$tp] ?? '') . ' |';
        }

        uasort($porDir, fn (array $a, array $b): int => $b['total'] <=> $a['total']);
        $out[] = "\n### Por diretório\n";
        $out[] = '| Diretório | Textos únicos | Ocorrências |';
        $out[] = '| --- | ---: | ---: |';
        foreach ($porDir as $dir => $d) {
            $out[] = '| `' . $dir . '/` | ' . ($d['unicos'] ?? 0) . ' | ' . $d['total'] . ' |';
        }

        $ordem = array_map('strval', array_keys($textos));
        usort($ordem, function (string $a, string $b) use ($textos): int {
            return ($textos[$b]['total'] <=> $textos[$a]['total']) ?: strcasecmp($a, $b);
        });

        $out[] = "\n### Textos (palavras soltas e frases)\n";
        $out[] = '_Ordenados por quantidade de ocorrências. "Onde aparece" mostra cada diretório com o total de ocorrências nele; '
            . "quando o texto está em até 6 arquivos, os nomes dos arquivos vêm entre parênteses (todos os detalhes, com linhas, estão no `--json`)._\n";
        $out[] = '| Texto | Qtd | Tipo | Onde aparece |';
        $out[] = '| --- | ---: | --- | --- |';
        foreach ($ordem as $texto) {
            $e = $textos[$texto];
            $out[] = '| ' . $this->celulaTexto($texto) . ' | ' . $e['total'] . ' | ' . $this->tiposTexto($e['tipos'], $rotulos) . ' | ' . $this->ondeAparece($e['arquivos']) . ' |';
        }

        if ($this->palavras) {
            $out[] = "\n" . $this->tabelaPalavras();
        }

        return implode("\n", $out) . "\n";
    }

    /** Vocabulário: cada palavra isolada (inclusive as de dentro das frases), com total e diretórios. */
    private function tabelaPalavras(): string
    {
        $vocab = [];
        foreach ($this->index->textos as $texto => $e) {
            $sem = Textos::semPlaceholders((string) $texto);
            if (preg_match_all('/\p{L}[\p{L}\p{M}\'’\-]*/u', $sem, $m) < 1) {
                continue;
            }
            foreach ($m[0] as $p) {
                $p = trim(Textos::minusculas($p), "-'’");
                if ($p === '') {
                    continue;
                }
                foreach ($e['arquivos'] as $rel => $info) {
                    $dir = $this->diretorioDe((string) $rel);
                    $vocab[$p]['total'] = ($vocab[$p]['total'] ?? 0) + $info['n'];
                    $vocab[$p]['dirs'][$dir] = ($vocab[$p]['dirs'][$dir] ?? 0) + $info['n'];
                }
            }
        }

        $out = ['### Vocabulário — palavras isoladas', ''];
        $out[] = '_' . count($vocab) . ' palavras distintas (maiúsculas/minúsculas contam como a mesma palavra; as tags de personalização não entram na contagem)._';
        $out[] = '';
        $out[] = '| Palavra | Qtd | Onde aparece |';
        $out[] = '| --- | ---: | --- |';

        $palavras = array_map('strval', array_keys($vocab));
        usort($palavras, fn (string $a, string $b): int => ($vocab[$b]['total'] <=> $vocab[$a]['total']) ?: strcmp($a, $b));

        foreach ($palavras as $p) {
            $dirs = $vocab[$p]['dirs'];
            arsort($dirs);
            $partes = [];
            foreach (array_slice($dirs, 0, 6, true) as $dir => $n) {
                $partes[] = '`' . $dir . '/` ×' . $n;
            }
            if (count($dirs) > 6) {
                $partes[] = '…+' . (count($dirs) - 6) . ' diretórios';
            }
            $out[] = '| ' . $this->celulaTexto($p) . ' | ' . $vocab[$p]['total'] . ' | ' . implode('<br>', $partes) . ' |';
        }

        return implode("\n", $out);
    }

    private function diretorioDe(string $rel): string
    {
        $d = dirname($rel);

        return ($d === '.' || $d === '') ? '(raiz)' : $d;
    }

    private function celulaTexto(string $texto): string
    {
        $t = str_replace('|', '\\|', Textos::cortar($texto, 400));
        if (str_contains($t, '`')) {
            return '`` ' . $t . ' ``';
        }

        return '`' . $t . '`';
    }

    private function tiposTexto(array $tipos, array $rotulos): string
    {
        arsort($tipos);

        return implode(', ', array_map(fn ($t) => $rotulos[$t] ?? $t, array_keys($tipos)));
    }

    /** Agrupa por diretório: `dir/` ×total (arquivo ×n, ...). */
    private function ondeAparece(array $arquivos): string
    {
        $porDir = [];
        foreach ($arquivos as $rel => $info) {
            $dir = $this->diretorioDe((string) $rel);
            $porDir[$dir]['total'] = ($porDir[$dir]['total'] ?? 0) + $info['n'];
            $porDir[$dir]['arq'][basename((string) $rel)] = $info['n'];
        }
        ksort($porDir);

        $comArquivos = count($arquivos) <= 6;
        $partes = [];
        foreach ($porDir as $dir => $d) {
            $txt = '`' . $dir . '/` ×' . $d['total'];
            if ($comArquivos) {
                $txt .= ' (' . implode(', ', array_map(
                    fn ($f, $n) => '`' . $f . '`' . ($n > 1 ? ' ×' . $n : ''),
                    array_keys($d['arq']),
                    $d['arq']
                )) . ')';
            }
            $partes[] = $txt;
        }
        if (count($partes) > 10) {
            $resto = count($partes) - 10;
            $partes = array_slice($partes, 0, 10);
            $partes[] = '…+' . $resto . ' diretórios';
        }

        return implode('<br>', $partes);
    }

    /** Seção "Tema escuro": onde aparece, com qual estilo, inconsistências e tags sem dark. */
    private function temaEscuro(): string
    {
        if ($this->darkArquivo !== null) {
            return "## Tema escuro (dark mode)\n\n_Gerado em arquivo separado: `" . basename($this->darkArquivo) . "` (opção `--dark-out`)._\n\n---\n";
        }

        return $this->corpoDark() . "\n---\n";
    }

    /** Documento só com o tema escuro (usado por --dark-out). */
    public function renderDarkAvulso(): string
    {
        $data = date('d/m/Y H:i');

        return "# 🌙 Tema escuro — {$this->projeto}\n\n> Gerado por **LaraMap** em {$data}\n\n" . $this->corpoDark();
    }

    private function corpoDark(): string
    {
        $idx = $this->index;
        $tags = $idx->darkTags;
        $out = ["## Tema escuro (dark mode)\n"];

        if ($tags === [] && $idx->darkCitacoes === [] && $idx->darkAtivacoes === [] && $idx->darkRegras === []) {
            return $out[0] . "_Nenhuma citação do tema escuro encontrada (`dark:`, `.dark`, `data-theme`, `prefers-color-scheme`...)._\n";
        }

        $analise = [];
        $comDark = 0;
        $arqDark = [];
        $arqFalta = [];
        $semDark = 0;
        foreach ($tags as $k => $t) {
            $analise[$k] = $this->analisarDark($t);
            if ($t['dark'] !== []) {
                $comDark++;
                $arqDark[$t['arquivo']] = ($arqDark[$t['arquivo']] ?? 0) + 1;
            }
            if ($analise[$k]['faltando'] !== []) {
                $semDark++;
                $arqFalta[$t['arquivo']] = ($arqFalta[$t['arquivo']] ?? 0) + 1;
            }
        }
        $usaDark = $comDark > 0 || $idx->darkRegras !== [];

        $out[] = '_' . $comDark . ' tags/regras com estilo para o tema escuro em ' . count($arqDark) . ' arquivos · '
            . $semDark . ' tags com cor que só funciona no tema claro e sem par `dark:` · '
            . count($idx->darkRegras) . ' regras CSS do tema escuro · '
            . (count($idx->darkAtivacoes) + count($idx->darkCitacoes)) . " citações de ativação/configuração._\n";
        $out[] = '_Como ler: **classe principal** = primeira classe que não é utilitária (`card`, `btn`, `modal`...). '
            . '**Claro → escuro** = a classe clara da tag e a classe `dark:` que a substitui. '
            . '"Sem dark" só é apontado para cores que dependem do tema: fundo/borda claros (`bg-white`, `border-gray-200`) e texto escuro (`text-gray-900`). '
            . "⚡ = classe vinda de expressão condicional (`{{ }}`, `@class`, `:class`). Fundos escuros fixos (`bg-dark`, `btn-dark`) não contam como tema._\n";

        // ---- 1. ativação / configuração
        $citas = [];
        foreach ($idx->darkAtivacoes as $a) {
            $citas[] = ['arquivo' => $a['arquivo'], 'linha' => $a['linha'], 'tag' => $a['tag'], 'tipo' => $a['tipo'], 'trecho' => $a['trecho']];
        }
        foreach ($idx->darkCitacoes as $c) {
            $citas[] = ['arquivo' => $c['arquivo'], 'linha' => $c['linha'], 'tag' => $c['tag'] ?? null, 'tipo' => $c['tipo'], 'trecho' => $c['trecho']];
        }
        usort($citas, fn (array $a, array $b): int => [$a['arquivo'], $a['linha']] <=> [$b['arquivo'], $b['linha']]);

        $out[] = "### Como o tema escuro é ativado e configurado\n";
        if ($citas === []) {
            $out[] = "_Nenhuma ativação encontrada (`class=\"dark\"`, `data-theme`, `darkMode`, `classList.toggle('dark')`...). Se o tema é controlado por outro mecanismo, use `--dark-padrao=REGEX`._\n";
        } else {
            $out[] = '| Local | Tag | Tipo | Trecho |';
            $out[] = '| --- | --- | --- | --- |';
            foreach ($citas as $c) {
                $out[] = '| `' . $c['arquivo'] . ':' . $c['linha'] . '` | ' . ($c['tag'] !== null ? '`' . $c['tag'] . '`' : '—') . ' | ' . $c['tipo'] . ' | ' . $this->celulaTexto($c['trecho']) . ' |';
            }
        }

        // ---- 2. resumo por arquivo
        $arquivos = array_unique(array_merge(array_keys($arqDark), array_keys($arqFalta)));
        if ($arquivos !== []) {
            usort($arquivos, fn (string $a, string $b): int => (($arqFalta[$b] ?? 0) <=> ($arqFalta[$a] ?? 0)) ?: strcmp($a, $b));
            $out[] = "\n### Resumo por arquivo\n";
            $out[] = '_Ordenado por quantidade de tags sem par escuro — por onde começar a corrigir._' . "\n";
            $out[] = '| Arquivo | Tags com dark | Tags sem dark |';
            $out[] = '| --- | ---: | ---: |';
            foreach ($arquivos as $a) {
                $out[] = '| `' . $a . '` | ' . ($arqDark[$a] ?? 0) . ' | ' . ($arqFalta[$a] ?? 0) . ' |';
            }
        }

        // ---- 3. mapa claro -> escuro por propriedade
        $mapa = [];
        foreach ($tags as $k => $t) {
            $loc = $t['arquivo'] . ':' . $t['linha'];
            foreach ($analise[$k]['pares'] as [$luz, $dk, $prop]) {
                $this->somarMapa($mapa, $prop, $luz ?? '(sem classe clara na tag)', $dk, $loc);
            }
            foreach ($analise[$k]['faltando'] as $l) {
                $this->somarMapa($mapa, $l['prop'], $l['token'], '— sem dark —', $loc);
            }
        }
        $sugestao = [];
        foreach ($mapa as $porLuz) {
            foreach ($porLuz as $luz => $ds) {
                $melhor = null;
                foreach ($ds as $dk => $info) {
                    if ($dk !== '— sem dark —' && ($melhor === null || $info['n'] > $ds[$melhor]['n'])) {
                        $melhor = $dk;
                    }
                }
                if ($melhor !== null) {
                    $sugestao[$luz] = $melhor;
                }
            }
        }

        if ($mapa !== []) {
            ksort($mapa);
            $out[] = "\n### Claro → escuro por propriedade\n";
            $out[] = '_Cada classe clara e o que o projeto usa no tema escuro para ela. ⚠️ = a mesma classe clara tem mais de um tratamento escuro (inclusive "sem dark")._' . "\n";
            $out[] = '| Propriedade | Claro | Escuro | Qtd | Exemplos |';
            $out[] = '| --- | --- | --- | ---: | --- |';
            foreach ($mapa as $prop => $porLuz) {
                uasort($porLuz, fn (array $a, array $b): int => array_sum(array_column($b, 'n')) <=> array_sum(array_column($a, 'n')));
                foreach ($porLuz as $luz => $ds) {
                    uasort($ds, fn (array $a, array $b): int => $b['n'] <=> $a['n']);
                    $diverge = count($ds) > 1;
                    foreach ($ds as $dk => $info) {
                        $out[] = '| `' . $prop . '` | ' . $this->celulaTexto((string) $luz) . ($diverge ? ' ⚠️' : '') . ' | ' . $this->celulaTexto((string) $dk) . ' | ' . $info['n'] . ' | ' . $this->exemplos($info['ex']) . ' |';
                    }
                }
            }
        }

        // ---- 4. inconsistências por componente
        $grupos = [];
        foreach ($tags as $k => $t) {
            $chave = $this->chaveComponente($t);
            if ($chave === null) {
                continue;
            }
            $g = &$grupos[$chave];
            $g['props'] ??= [];
            $g['n'] = ($g['n'] ?? 0) + 1;
            $g['tags'][$t['tag']] = true;
            if ($t['dark'] !== []) {
                $g['comDark'] = ($g['comDark'] ?? 0) + 1;
            }
            $loc = $t['arquivo'] . ':' . $t['linha'];
            foreach ($analise[$k]['pares'] as [$luz, $dk, $prop]) {
                $this->somarMapa($g['props'], $prop, '', $dk, $loc);
            }
            foreach ($analise[$k]['faltando'] as $l) {
                $this->somarMapa($g['props'], $l['prop'], '', '— sem dark —', $loc);
            }
            unset($g);
        }

        $cssPorClasse = [];
        foreach ($idx->darkRegras as $r) {
            foreach ($r['classes'] as $c) {
                $cssPorClasse[$c][] = $r;
            }
        }

        $divergentes = [];
        foreach ($grupos as $chave => $g) {
            $props = [];
            if ($g['n'] >= 2) {
                foreach ($g['props'] ?? [] as $prop => $porLuz) {
                    $ds = $porLuz[''] ?? [];
                    if (count($ds) > 1) {
                        $props[$prop] = $ds;
                    }
                }
            }
            $cssRegras = $cssPorClasse[ltrim($chave, '.')] ?? [];
            $mistura = $cssRegras !== [] && ($g['comDark'] ?? 0) > 0;
            if ($props !== [] || $mistura) {
                $divergentes[$chave] = ['n' => $g['n'], 'tags' => array_keys($g['tags']), 'props' => $props, 'css' => $mistura ? $cssRegras : []];
            }
        }
        uasort($divergentes, fn (array $a, array $b): int => (count($b['props']) <=> count($a['props'])) ?: ($b['n'] <=> $a['n']));

        $out[] = "\n### Inconsistências por componente\n";
        if ($divergentes === []) {
            $out[] = '_Nenhum componente (mesma classe principal) com estilos escuros diferentes entre ocorrências._' . "\n";
        } else {
            $out[] = '_Mesma classe principal, estilo escuro diferente em cada lugar — ou estilizada por `dark:` e por regra CSS ao mesmo tempo. '
                . "Cada tabela mostra só as propriedades que divergem._\n";
            foreach ($divergentes as $chave => $g) {
                $out[] = '#### `' . $chave . '` — ' . $g['n'] . ' ocorrências em ' . implode(', ', array_map(fn ($t) => '`' . $t . '`', $g['tags']));
                $out[] = '';
                if ($g['css'] !== []) {
                    $out[] = '_Também estilizado por regra CSS, além das classes `dark:`:_';
                    foreach ($g['css'] as $r) {
                        $out[] = '- ' . $this->celulaTexto($r['seletor']) . ' → ' . $this->celulaTexto($r['estilo']) . ' (`' . $r['arquivo'] . ':' . $r['linha'] . '`)';
                    }
                    $out[] = '';
                }
                if ($g['props'] !== []) {
                    $out[] = '| Propriedade | Valor no tema escuro | Qtd | Exemplos |';
                    $out[] = '| --- | --- | ---: | --- |';
                    foreach ($g['props'] as $prop => $ds) {
                        uasort($ds, fn (array $a, array $b): int => $b['n'] <=> $a['n']);
                        foreach ($ds as $dk => $info) {
                            $out[] = '| `' . $prop . '` | ' . $this->celulaTexto((string) $dk) . ' | ' . $info['n'] . ' | ' . $this->exemplos($info['ex']) . ' |';
                        }
                    }
                    $out[] = '';
                }
            }
        }

        // ---- 5. tags que deveriam ter dark
        $out[] = "### Tags que deveriam ter tema escuro\n";
        if (! $usaDark) {
            $out[] = '_O projeto ainda não usa estilos para o tema escuro (nenhum `dark:` nem regra CSS), então esta análise não se aplica._' . "\n";
        } elseif ($semDark === 0) {
            $out[] = "_Nenhuma tag com cor exclusiva do tema claro sem par escuro._\n";
        } else {
            $out[] = '_Tags com fundo/borda claros ou texto escuro **sem** a classe `dark:` equivalente. A sugestão é o que o projeto mais usa para aquela classe. '
                . "Uma tag dentro de um elemento que já define o texto escuro pode herdar a cor — confira antes de corrigir._\n";
            $out[] = '| Local | Tag | Classe principal | Falta dark para | Sugestão |';
            $out[] = '| --- | --- | --- | --- | --- |';
            foreach ($tags as $k => $t) {
                if ($analise[$k]['faltando'] === []) {
                    continue;
                }
                $falta = [];
                $sug = [];
                foreach ($analise[$k]['faltando'] as $l) {
                    $falta[] = '`' . $l['token'] . '`';
                    $sug[] = isset($sugestao[$l['token']]) ? '`' . $sugestao[$l['token']] . '`' : '_(sem padrão no projeto)_';
                }
                $out[] = '| `' . $t['arquivo'] . ':' . $t['linha'] . '` | `' . $t['tag'] . '`' . ($t['dinamico'] ? ' ⚡' : '') . ' | '
                    . ($t['principal'] !== null ? '`' . $t['principal'] . '`' : '—') . ' | ' . implode('<br>', $falta) . ' | ' . implode('<br>', $sug) . ' |';
            }
        }

        // ---- 6. CSS
        if ($idx->darkRegras !== [] || $idx->darkClaroCss !== []) {
            $out[] = "\n### Estilos em arquivos CSS/SCSS\n";
            if ($idx->darkRegras !== []) {
                $out[] = '| Local | Seletor | Estilo no tema escuro |';
                $out[] = '| --- | --- | --- |';
                foreach ($idx->darkRegras as $r) {
                    $out[] = '| `' . $r['arquivo'] . ':' . $r['linha'] . '` | ' . $this->celulaTexto($r['seletor']) . ' | ' . $this->celulaTexto($r['estilo']) . ' |';
                }
            }

            $cobertas = [];
            foreach ($idx->darkRegras as $r) {
                foreach ($r['classes'] as $c) {
                    $cobertas[$c] = true;
                }
            }
            foreach ($tags as $t) {
                if ($t['tag'] === '@apply' && $t['dark'] !== [] && $t['principal'] !== null) {
                    $cobertas[ltrim($t['principal'], '.')] = true;
                }
            }
            $semRegra = [];
            foreach ($idx->darkClaroCss as $c) {
                if (! isset($cobertas[$c['classe']])) {
                    $semRegra[$c['classe']][] = $c;
                }
            }
            if ($semRegra !== []) {
                $out[] = "\n#### Classes CSS com cor clara e sem regra para o tema escuro\n";
                $out[] = '| Classe | Estilo claro | Local | Uso nas views (total / com `dark:`) |';
                $out[] = '| --- | --- | --- | --- |';
                uksort($semRegra, fn (string $a, string $b): int => (($idx->darkUso[$b]['total'] ?? 0) <=> ($idx->darkUso[$a]['total'] ?? 0)) ?: strcmp($a, $b));
                foreach ($semRegra as $classe => $lista) {
                    $estilos = array_map(fn (array $c) => $c['prop'] . ': ' . $c['valor'], array_slice($lista, 0, 3));
                    $uso = $idx->darkUso[$classe] ?? ['total' => 0, 'dark' => 0];
                    $out[] = '| `.' . $classe . '` | ' . $this->celulaTexto(implode('; ', $estilos)) . ' | `' . $lista[0]['arquivo'] . ':' . $lista[0]['linha'] . '` | '
                        . ($uso['total'] ?? 0) . ' / ' . ($uso['dark'] ?? 0) . ' |';
                }
            }
        }

        // ---- 7. inventário
        $porArquivo = [];
        foreach ($tags as $k => $t) {
            if ($t['dark'] !== []) {
                $porArquivo[$t['arquivo']][] = $k;
            }
        }
        $out[] = "\n### Inventário: todas as tags com estilo para o tema escuro\n";
        if ($porArquivo === []) {
            $out[] = "_Nenhuma tag com classe `dark:` encontrada._\n";
        } else {
            ksort($porArquivo);
            foreach ($porArquivo as $arq => $ks) {
                $out[] = '#### `' . $arq . '`';
                $out[] = '';
                $out[] = '| Linha | Tag | Classe principal | Claro → escuro | Outras classes dark |';
                $out[] = '| ---: | --- | --- | --- | --- |';
                foreach ($ks as $k) {
                    $t = $tags[$k];
                    $pares = array_map(
                        fn (array $p) => ($p[0] !== null ? '`' . $p[0] . '`' : '_(sem claro)_') . ' → `' . $p[1] . '`',
                        $analise[$k]['pares']
                    );
                    $outras = array_map(fn ($d) => '`' . $d . '`', $analise[$k]['outras']);
                    $out[] = '| ' . $t['linha'] . ' | `' . $t['tag'] . '`' . ($t['dinamico'] ? ' ⚡' : '') . ' | '
                        . ($t['principal'] !== null ? '`' . $t['principal'] . '`' : '—') . ' | ' . ($pares === [] ? '—' : implode('<br>', $pares)) . ' | '
                        . ($outras === [] ? '—' : implode(' ', $outras)) . ' |';
                }
                $out[] = '';
            }
        }

        return implode("\n", $out) . "\n";
    }

    /**
     * Para uma tag: pares claro→escuro, classes cuja cor só vale no tema claro e não têm par, e as demais classes dark.
     *
     * @return array{pares:array,faltando:array,outras:array}
     */
    private function analisarDark(array $t): array
    {
        $darkItens = [];
        $outras = [];
        foreach ($t['dark'] as $i => $d) {
            $a = TemaClasses::analisar($d);
            if ($a['prop'] !== null) {
                $darkItens[] = ['t' => $d, 'chave' => $a['var'] . '|' . $a['prop'], 'prop' => $a['prop'], 'g' => $t['darkG'][$i] ?? null];
            } else {
                $outras[] = $d;
            }
        }

        // pares: o dark de um ramo ('a dark:b' : 'c') só se liga às classes claras do mesmo ramo (ou às estáticas)
        $pares = [];
        foreach ($darkItens as $d) {
            $cands = array_values(array_filter($t['luz'], fn (array $l): bool => $l['chave'] === $d['chave']));
            if ($d['g'] !== null) {
                $mesmo = array_values(array_filter($cands, fn (array $l): bool => ($l['g'] ?? null) === $d['g']));
                $cands = $mesmo !== [] ? $mesmo : array_values(array_filter($cands, fn (array $l): bool => ($l['g'] ?? null) === null));
            }
            if ($cands === []) {
                $pares[] = [null, $d['t'], $d['prop']];
            } else {
                foreach ($cands as $l) {
                    $pares[] = [$l['token'], $d['t'], $d['prop']];
                }
            }
        }

        $faltando = [];
        foreach ($t['luz'] as $l) {
            if (! $l['precisa']) {
                continue;
            }
            $tem = false;
            foreach ($darkItens as $d) {
                if ($d['chave'] === $l['chave'] && ($d['g'] === null || ($l['g'] ?? null) === null || $d['g'] === $l['g'])) {
                    $tem = true;
                    break;
                }
            }
            if (! $tem) {
                $faltando[] = $l;
            }
        }

        return ['pares' => $pares, 'faltando' => $faltando, 'outras' => $outras];
    }

    private function somarMapa(array &$mapa, string $prop, string $luz, string $dark, string $loc): void
    {
        $e = &$mapa[$prop][$luz][$dark];
        if ($e === null) {
            $e = ['n' => 0, 'ex' => []];
        }
        $e['n']++;
        if (count($e['ex']) < 3) {
            $e['ex'][] = $loc;
        }
        unset($e);
    }

    private function exemplos(array $ex): string
    {
        return implode('<br>', array_map(fn ($e) => '`' . $e . '`', $ex));
    }

    /** Agrupa por classe principal (ou, sem ela, pela tag + classe de fundo clara). */
    private function chaveComponente(array $t): ?string
    {
        if ($t['principal'] !== null) {
            $p = (string) $t['principal'];

            return str_starts_with($p, '<') ? $p : '.' . ltrim($p, '.');
        }
        foreach ($t['luz'] as $l) {
            if ($l['prop'] === 'bg' && $l['chave'] === '|bg') {
                return '<' . $t['tag'] . '> ' . $l['token'];
            }
        }

        return null;
    }

    private function arquivosIgnorados(): string
    {
        $out = ["## Arquivos ignorados\n"];
        $out[] = '<details><summary>' . count($this->scan['padrao']) . " arquivos considerados padrão da stack (clique para ver)</summary>\n";
        $out[] = implode("\n", array_map(fn ($f) => '- `' . $f . '`', $this->scan['padrao']));
        $out[] = "\n</details>";

        return implode("\n", $out) . "\n";
    }

    // ------------------------------------------------------------------

    private function modelsDaClasse(array $class): array
    {
        $models = [];
        foreach ($class['metodos'] as $metodo) {
            foreach (['chamadas_estaticas', 'chamadas_instancia', 'instanciacoes'] as $bucket) {
                foreach (array_keys($metodo['refs'][$bucket]) as $classe) {
                    if ($this->index->isModel($classe)) {
                        $models[] = $classe;
                    }
                }
            }
            foreach ($metodo['refs']['propriedades'] as $tipo) {
                if ($this->index->isModel($tipo)) {
                    $models[] = $tipo;
                }
            }
        }
        foreach ($class['propriedades'] as $prop) {
            if ($prop['tipo'] !== null && $this->index->isModel($prop['tipo'])) {
                $models[] = $prop['tipo'];
            }
        }
        foreach ($class['metodos'] as $metodo) {
            foreach ($metodo['refs']['relacionamentos'] ?? [] as $relacao) {
                $models[] = $relacao['classe'];
            }
        }

        return array_values(array_unique($models));
    }

    private function tagClasse(string $fqcn): string
    {
        if (isset($this->index->classes[$fqcn])) {
            $cat = $this->index->classes[$fqcn]['categoria'] ?? 'classe';

            return $cat === 'classe' ? '' : ' _(' . $cat . ')_';
        }

        foreach (['Illuminate', 'Livewire', 'Spatie', 'Symfony', 'Carbon', 'Laravel', 'Barryvdh', 'Maatwebsite', 'PhpOffice'] as $vendor) {
            if (str_starts_with($fqcn, $vendor)) {
                return ' _(framework)_';
            }
        }

        return '';
    }

    private function curto(string $fqcn): string
    {
        if (str_contains($fqcn, '::')) {
            [$classe, $metodo] = explode('::', $fqcn, 2);

            return $this->curto($classe) . '::' . $metodo;
        }
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function listaCodigo(array $itens): string
    {
        return implode(', ', array_map(fn ($i) => '`' . $this->curto((string) $i) . '`', array_unique($itens)));
    }

    /** Acha, entre os gatilhos coletados no método, os parâmetros do primeiro que bate com tipo+alvo. */
    private function paramsDoGatilho(array $gatilhos, string $tipo, string $alvo): string
    {
        foreach ($gatilhos as $g) {
            if ($g['tipo'] === $tipo && $g['alvo'] === $alvo) {
                return implode(', ', array_map(fn ($p) => $this->truncar($p, 40), $g['params']));
            }
        }

        return '';
    }

    private function parenteses(string $conteudo): string
    {
        return $conteudo === '' ? '' : '(' . $conteudo . ')';
    }

    private function truncar(string $texto, int $tamanho): string
    {
        $texto = trim((string) preg_replace('/\s+/', ' ', $texto));

        return strlen($texto) > $tamanho ? substr($texto, 0, $tamanho - 1) . '…' : $texto;
    }
}

final class Main
{
    public const VERSION = '1.0.0';

    public static function run(array $argv): int
    {
        $opts = [
            'out' => null,
            'json' => null,
            'baseline' => null,
            'include-stock' => false,
            'no-mermaid' => false,
            'no-sequence' => false,
            'no-erd' => false,
            'no-strings' => false,
            'no-dark' => false,
            'dark-out' => null,
            'dark-padrao' => null,
            'strings-todas' => false,
            'strings-out' => null,
            'palavras' => false,
            'ignore' => '',
            'quiet' => false,
        ];
        $projeto = null;

        foreach (array_slice($argv, 1) as $arg) {
            if ($arg === '-h' || $arg === '--help') {
                self::help();

                return 0;
            }
            if ($arg === '-v' || $arg === '--version') {
                echo 'LaraMap ' . self::VERSION . "\n";

                return 0;
            }
            if (str_starts_with($arg, '--')) {
                [$k, $v] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
                if (! array_key_exists($k, $opts)) {
                    fwrite(STDERR, "Opção desconhecida: --{$k}\n");

                    return 1;
                }
                $opts[$k] = $v;
                continue;
            }
            $projeto = $arg;
        }

        $root = realpath($projeto ?? getcwd());
        if ($root === false || ! is_dir($root)) {
            fwrite(STDERR, "Diretório do projeto não encontrado.\n");

            return 1;
        }

        if (! is_file($root . '/artisan')) {
            fwrite(STDERR, "Aviso: '{$root}' não parece a raiz de um projeto Laravel (artisan não encontrado).\n");
        }

        $log = fn (string $msg) => $opts['quiet'] ? null : fwrite(STDOUT, $msg . "\n");
        $inicio = microtime(true);

        $log("\n  LaraMap " . self::VERSION . ' — mapeando ' . basename($root));
        $log('  ' . str_repeat('─', 52));

        if (is_string($opts['dark-padrao'])) {
            if (@preg_match('~' . $opts['dark-padrao'] . '~', '') === false) {
                fwrite(STDERR, "--dark-padrao inválido: '{$opts['dark-padrao']}' não é uma expressão regular.\n");

                return 1;
            }
            TemaClasses::$padraoExtra = $opts['dark-padrao'];
        }

        $scanner = new Scanner(
            $root,
            (bool) $opts['include-stock'],
            is_string($opts['baseline']) ? $opts['baseline'] : null,
            array_filter(array_map('trim', explode(',', (string) $opts['ignore'])))
        );
        $scan = $scanner->scan();

        $log('  Arquivos próprios encontrados : ' . count($scan['relevantes']));
        $log('  Arquivos padrão ignorados     : ' . count($scan['padrao']));

        $index = new Indexer();
        $lidos = 0;

        foreach ($scan['relevantes'] as $rel) {
            $abs = $root . '/' . $rel;
            $code = @file_get_contents($abs);
            if ($code === false) {
                $index->avisos[] = "Não foi possível ler {$rel}";
                continue;
            }

            if (str_ends_with($rel, '.php')) {
                try {
                    $index->addFeatures($rel, FeatureScanner::extrair($rel, $code));
                } catch (\Throwable $e) {
                    $index->avisos[] = "Features: falha ao analisar {$rel}: " . $e->getMessage();
                }
            }

            if (! $opts['no-dark']) {
                try {
                    $index->addDark($rel, ExtratorDark::extrair($rel, $code));
                } catch (\Throwable $e) {
                    $index->avisos[] = "Tema escuro: falha ao analisar {$rel}: " . $e->getMessage();
                }
            }

            if (! $opts['no-strings']) {
                try {
                    $index->addTextos($rel, Extrator::extrair($rel, $code, (bool) $opts['strings-todas']));
                } catch (\Throwable $e) {
                    $index->avisos[] = "Textos: falha ao extrair de {$rel}: " . $e->getMessage();
                }
            }

            if (str_ends_with($rel, '.blade.php')) {
                $blade = BladeAnalyzer::analyze($rel, $code);
                $index->addBlade($blade);
                $lidos++;

                if (VoltAnalyzer::isVolt($code)) {
                    try {
                        $volt = VoltAnalyzer::analyze($rel, $code, $blade['view']);
                        if ($volt !== null) {
                            $index->addVoltComponent($blade['view'], $volt);
                        }
                    } catch (\Throwable $e) {
                        $index->avisos[] = "Volt: falha ao analisar {$rel}: " . $e->getMessage();
                    }
                }

                continue;
            }

            if (! str_ends_with($rel, '.php')) {
                continue;
            }

            try {
                $file = PhpAnalyzer::analyze($rel, $code);
            } catch (\Throwable $e) {
                $index->avisos[] = "Falha ao analisar {$rel}: " . $e->getMessage();
                continue;
            }

            $index->addPhpFile($file);
            $lidos++;

            if (str_starts_with($rel, 'routes/')) {
                $index->addRoutes(RouteAnalyzer::analyze($rel, $code, $file['namespace'], $file['uses']));
            }

            if (str_starts_with($rel, 'database/migrations/')) {
                try {
                    $index->mergeMigration(MigrationAnalyzer::analyze($rel, $code));
                } catch (\Throwable $e) {
                    $index->avisos[] = "Migration: falha ao analisar {$rel}: " . $e->getMessage();
                }
            }
        }

        $log('  Arquivos lidos                : ' . $lidos);

        $index->build();

        $log('  Classes mapeadas              : ' . count($index->classes));
        $log('  Views mapeadas                : ' . count($index->blades));
        $log('  Rotas mapeadas                : ' . count($index->rotas));
        $log('  Verificações de feature       : ' . count($index->features));
        if (! $opts['no-strings']) {
            $log('  Textos únicos                 : ' . count($index->textos));
        }
        if (! $opts['no-dark']) {
            $log('  Tags/regras com tema escuro   : ' . count(array_filter($index->darkTags, fn (array $t): bool => $t['dark'] !== [])));
        }

        $textosArquivo = is_string($opts['strings-out']) ? $opts['strings-out'] : null;
        $darkArquivo = is_string($opts['dark-out']) ? $opts['dark-out'] : null;
        $writer = new MarkdownWriter(
            $index,
            $scan,
            basename($root),
            ! $opts['no-mermaid'],
            ! $opts['no-sequence'],
            ! $opts['no-erd'],
            ! $opts['no-strings'],
            (bool) $opts['palavras'],
            $textosArquivo,
            ! $opts['no-dark'],
            $darkArquivo
        );
        $markdown = $writer->render();

        if ($darkArquivo !== null && ! $opts['no-dark']) {
            $darkMd = $writer->renderDarkAvulso();
            file_put_contents($darkArquivo, $darkMd);
            $log('  ✓ Tema escuro gerado: ' . $darkArquivo . ' (' . number_format(strlen($darkMd) / 1024, 1) . ' KB)');
        }

        if ($textosArquivo !== null && ! $opts['no-strings']) {
            $textosMd = $writer->renderTextosAvulso();
            file_put_contents($textosArquivo, $textosMd);
            $log('  ✓ Textos gerados: ' . $textosArquivo . ' (' . number_format(strlen($textosMd) / 1024, 1) . ' KB)');
        }

        $saida = is_string($opts['out']) ? $opts['out'] : $root . '/MAPA-DO-SISTEMA.md';
        file_put_contents($saida, $markdown);
        $log("\n  ✓ Markdown gerado: " . $saida . ' (' . number_format(strlen($markdown) / 1024, 1) . ' KB)');

        if (is_string($opts['json'])) {
            file_put_contents($opts['json'], json_encode([
                'gerado_em' => date('c'),
                'projeto' => basename($root),
                'scan' => $scan,
                'classes' => $index->classes,
                'views' => $index->blades,
                'rotas' => $index->rotas,
                'chamadores' => $index->callers,
                'models' => $index->modelUsage,
                'eventos' => $index->eventos,
                'textos' => $index->textos,
                'features' => $index->features,
                'dark' => [
                    'tags' => $index->darkTags,
                    'ativacoes' => $index->darkAtivacoes,
                    'citacoes' => $index->darkCitacoes,
                    'regras' => $index->darkRegras,
                    'claroCss' => $index->darkClaroCss,
                    'uso' => $index->darkUso,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $log('  ✓ JSON gerado: ' . $opts['json']);
        }

        foreach ($index->avisos as $aviso) {
            $log('  ! ' . $aviso);
        }

        $log('  Tempo: ' . number_format(microtime(true) - $inicio, 2) . "s\n");

        return 0;
    }

    private static function help(): void
    {
        $versao = self::VERSION;
        echo <<<TXT

          LaraMap {$versao} — mapa de fluxo para projetos Laravel 12 (Sail + Livewire + Spatie)

          USO
            php laramap.php [caminho-do-projeto] [opções]

          OPÇÕES
            --out=ARQUIVO        Caminho do .md gerado (padrão: <projeto>/MAPA-DO-SISTEMA.md)
            --json=ARQUIVO       Também grava o mapa em JSON (útil para outras ferramentas)
            --baseline=DIR       Diretório de um Laravel limpo; compara por hash e considera
                                 padrão apenas o que estiver idêntico
            --include-stock      Não ignora os arquivos padrão da stack
            --no-mermaid         Não gera o grafo Mermaid de dependências
            --no-sequence        Não gera os diagramas de sequência (um por rota/ação de view)
            --no-erd             Não gera o diagrama de banco de dados (entidade-relacionamento)
            --no-strings         Não gera a seção de textos e strings do sistema
            --strings-out=ARQ    Grava os textos num arquivo separado (a seção principal só aponta para ele)
            --strings-todas      Lista TODA string, sem o filtro que descarta chaves, classes CSS, caminhos etc.
            --palavras           Acrescenta o vocabulário: cada palavra isolada, com total e diretórios
            --no-dark            Não gera a seção de tema escuro (dark mode)
            --dark-out=ARQ       Grava a análise de tema escuro num arquivo separado
            --dark-padrao=REGEX  Classes que o projeto usa como "dark" além de dark: (ex.: '^dark-')
            --ignore=GLOB,GLOB   Padrões adicionais a ignorar (ex: app/Legacy/*,tests/*)
            --quiet              Silencia a saída no terminal
            -h, --help           Esta ajuda

          EXEMPLOS
            php laramap.php .
            php laramap.php . --json=mapa.json --ignore=tests/*
            php laramap.php /var/www/app --baseline=/tmp/laravel-limpo


        TXT;
    }
}

exit(Main::run($argv));
