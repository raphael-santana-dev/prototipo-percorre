<!DOCTYPE html>
<html>
<head>
    <title>Erro Interno</title>
</head>
<body style="padding: 20px; font-family: sans-serif;">
    <h2>Ops! Ocorreu um erro interno.</h2>
    
    <div style="background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;">
        <p><strong>Mensagem do Erro:</strong> {{ isset($exception) && method_exists($exception, 'getMessage') ? $exception->getMessage() : 'Ocorreu um erro interno no servidor.' }}</p>
        <p><strong>Arquivo:</strong> {{ isset($exception) && method_exists($exception, 'getFile') ? $exception->getFile() : 'Arquivo não disponível' }}</p>
        <p><strong>Linha:</strong> {{ isset($exception) && method_exists($exception, 'getLine') ? $exception->getLine() : 'Linha não disponível' }}</p>
    </div>
</body>
</html>