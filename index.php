<?php
// conexão com o banco de dados postgres
$dsn = 'pgsql:host=databases_zordin;port=5432;dbname=databases';
$user = 'postgres';
$password = '55081546289173748df1';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage() . " (" . $e->getCode() . ")");
}

// funções para buscar dados
function fetchContas($pdo, $table, $filter) {
    $sql = "SELECT * FROM $table WHERE $filter AND user_id = '5511916674140'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// calcula o saldo
function calcularSaldo($pdo) {
    $recebido = $pdo->query("SELECT SUM(valor) as total FROM a_receber WHERE status = 'pago' AND user_id = '5511916674140'")->fetch()['total'] ?? 0;
    $pago = $pdo->query("SELECT SUM(valor) as total FROM a_pagar WHERE status = 'pago' AND user_id = '5511916674140'")->fetch()['total'] ?? 0;
    return $recebido - $pago;
}

// filtros para dia, semana e mês
$hoje = date('Y-m-d');
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$inicioMes = date('Y-m-01');
$dados = [
    'hoje' => [
        'a_receber' => fetchContas($pdo, 'a_receber', "data = '$hoje'"),
        'a_pagar' => fetchContas($pdo, 'a_pagar', "data = '$hoje'"),
    ],
    'semana' => [
        'a_receber' => fetchContas($pdo, 'a_receber', "data BETWEEN '$inicioSemana' AND '$hoje'"),
        'a_pagar' => fetchContas($pdo, 'a_pagar', "data BETWEEN '$inicioSemana' AND '$hoje'"),
    ],
    'mes' => [
        'a_receber' => fetchContas($pdo, 'a_receber', "data BETWEEN '$inicioMes' AND '$hoje'"),
        'a_pagar' => fetchContas($pdo, 'a_pagar', "data BETWEEN '$inicioMes' AND '$hoje'"),
    ]
];
$saldo = calcularSaldo($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zordin - Dashboard Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
        }
        .card {
            background: linear-gradient(45deg, #1a1a1a, #2b2b2b);
            margin-top: 20px;
        }
        .card-receber {
            background: linear-gradient(45deg, #28a745, #1d7e32) !important;
        }
        .card-pagar {
            background: linear-gradient(45deg, #dc3545, #a62634) !important;
        }
        h5, h2, ul li {
            color: #ffffff;
        }
        .status {
            text-align: right;
            text-transform: uppercase;
            position: absolute;
            right: 10px;
            top: 10px;
        }
        .card ul {
            position: relative;
        }
        @media (min-width: 992px) {
            .card {
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center">Zordin - Sistema Financeiro</h1>
        <div class="row my-4">
            <div class="col-md-4">
                <div class="card p-3">
                    <h5>Saldo em Conta</h5>
                    <h2 class="text-success">R$ <?= number_format($saldo, 2, ',', '.') ?></h2>
                </div>
            </div>
        </div>

        <div class="row my-4">
            <?php foreach (['hoje' => 'Hoje', 'semana' => 'Semana', 'mes' => 'Mês'] as $key => $label): ?>
                <div class="col-12">
                    <div class="card card-receber p-3">
                        <h5>Contas a Receber - <?= $label ?></h5>
                        <ul>
                            <?php foreach ($dados[$key]['a_receber'] as $receber): ?>
                                <li>
                                    <?= $receber['descricao'] ?> - R$ <?= number_format($receber['valor'], 2, ',', '.') ?><?= date('d/m/Y', strtotime($receber['data'])) ?>
                                    <span class="status">- <?= strtoupper($receber['status']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card card-pagar p-3">
                        <h5>Contas a Pagar - <?= $label ?></h5>
                        <ul>
                            <?php foreach ($dados[$key]['a_pagar'] as $pagar): ?>
                                <li>
                                    <?= $pagar['descricao'] ?> - R$ <?= number_format($pagar['valor'], 2, ',', '.') ?><?= date('d/m/Y', strtotime($pagar['data'])) ?>
                                    <span class="status">- <?= strtoupper($pagar['status']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
