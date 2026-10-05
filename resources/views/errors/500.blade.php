<!DOCTYPE html>
<html>
<head>
    <title>Erro Interno</title>
</head>
<body style="padding: 20px; font-family: sans-serif;">
    <h2>Ops! Ocorreu um erro interno.</h2>
    
    <div style="background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;">
        <p><strong>Mensagem do Erro:</strong> {{ $exception->getMessage() }}</p>
        <p><strong>Arquivo:</strong> {{ $exception->getFile() }}</p>
        <p><strong>Linha:</strong> {{ $exception->getLine() }}</p>
    </div>
</body>
</html>