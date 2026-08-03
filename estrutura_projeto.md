# Estrutura do Projeto

## Raiz do Projeto

- artisan
- compose.yaml
- composer.json
- package.json
- phpunit.xml
- README.md
- vite.config.js
- app/
- bootstrap/
- config/
- database/
- public/
- resources/
- routes/
- storage/
- tests/
- vendor/

## app/

- Helpers/
  - BreadcrumbHelper.php
- Http/
  - Controllers/
  - Livewire/
    - Components/
- Models/
  - CampoFormulario.php
  - Ciclo.php
  - Curso.php
  - Etapa.php
  - Inscricao.php
  - StatusInscricao.php
  - User.php
  - Modules/
    - ACL/
    - Auth/
    - Corporate/
    - Curso/
    - Dashboard/
    - FeatureToggle/
    - Period/
    - Registration/
    - Shared/
    - Student/
    - Turno/
    - Unidade/
    - Website/
    - Providers/
      - AppServiceProvider.php
- Traits/
  - ComPadraoListagem.php
  - FiltraPorVinculo.php
  - Tenantable.php
  - WithCepConsulta.php
  - WithToggleStatus.php

## bootstrap/

- app.php
- providers.php
- cache/
  - packages.php
  - services.php

## config/

- app.php
- auth.php
- cache.php
- database.php
- filesystems.php
- livewire.php
- logging.php
- mail.php
- permission.php
- queue.php
- services.php
- session.php

## database/

- factories/
  - UserFactory.php
- migrations/
  - 0001_01_01_000000_create_users_table.php
  - 0001_01_01_000001_create_cache_table.php
  - 0001_01_01_000002_create_jobs_table.php
  - 2026_07_22_202914_create_permission_tables.php
  - 2026_07_23_140910_create_features_table.php
  - 2026_07_23_142434_add_module_to_features_and_permissions.php
  - 2026_07_23_145037_add_expires_at_to_model_has_permissions.php
  - 2026_07_23_150348_create_students_table.php
  - 2026_07_23_173600_create_turnos_table.php
  - ...
- seeders/
  - DatabaseSeeder.php
  - ...

## public/

- hot
- index.php
- robots.txt
- build/
  - ...

## resources/

- css/
  - ...
- images/
  - ...
- js/
  - ...
- views/
  - ...

## routes/

- console.php
- web.php

## storage/

- app/
- framework/
- logs/

## tests/

- TestCase.php
- Feature/
- Unit/

## vendor/

- autoload.php
- bin/
- blade-ui-kit/
- brick/
- carbonphp/
- composer/
- dflydev/
- doctrine/
- dragonmantank/
- egulias/
- fakerphp/
- filp/
- fruitcake/
- graham-campbell/
- guzzlehttp/
- hamcrest/
- myclabs/
- nesbot/
- nette/
- nikic/
- nunomaduro/
- phar-io/
- phpoption/
- phpunit/
- psr/
- psy/
- ralouphie/
- ramsey/
- sebastian/
- spatie/
- staabm/
- symfony/
- theseer/
- tijsverkoyen/
- vlucas/
- voku/
