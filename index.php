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

// Atualiza o status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'], $_POST['table'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'] === 'pago' ? 'nao pago' : 'pago';
    $table = $_POST['table'];

    $stmt = $pdo->prepare("UPDATE $table SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $id]);
    exit;
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

// calcula os totais
function calcularTotal($pdo, $table, $filter) {
    return $pdo->query("SELECT SUM(valor) as total FROM $table WHERE $filter AND user_id = '5511916674140'")->fetch()['total'] ?? 0;
}

// filtros para dia, semana e mês
$hoje = date('Y-m-d');
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$inicioMes = date('Y-m-01');
$totais = [
    'hoje' => [
        'a_receber' => calcularTotal($pdo, 'a_receber', "data = '$hoje'"),
        'a_pagar' => calcularTotal($pdo, 'a_pagar', "data = '$hoje'"),
    ],
    'semana' => [
        'a_receber' => calcularTotal($pdo, 'a_receber', "data BETWEEN '$inicioSemana' AND '$hoje'"),
        'a_pagar' => calcularTotal($pdo, 'a_pagar', "data BETWEEN '$inicioSemana' AND '$hoje'"),
    ],
    'mes' => [
        'a_receber' => calcularTotal($pdo, 'a_receber', "data BETWEEN '$inicioMes' AND '$hoje'"),
        'a_pagar' => calcularTotal($pdo, 'a_pagar', "data BETWEEN '$inicioMes' AND '$hoje'"),
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
        h5, h2, ul li, .new-card h5 {
            color: #ffffff;
        }
        .new-card ul {
            list-style: none;
            padding: 0;
        }
        .new-card ul li {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            flex-direction: column;
        }
        .btn-status {
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            text-transform: uppercase;
            font-size: 12px;
        }
        .btn-status.pago {
            background-color: #28a745;
            color: #ffffff;
        }
        .btn-status.nao-pago {
            background-color: #ff0000 !important;
            color: #ffffff;
        }
        @media (min-width: 992px) {
            .card {
                display: block;
                width: 100%;
            }
        }
    </style>
    <script>
        async function toggleStatus(id, status, table) {
            const newStatus = status === 'pago' ? 'nao pago' : 'pago';
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            formData.append('table', table);

            await fetch('', {
                method: 'POST',
                body: formData
            });

            const button = document.getElementById(`status-${table}-${id}`);
            button.className = `btn btn-status ${newStatus.replace(' ', '-')}`;
            button.innerText = newStatus.toUpperCase();
        }
    </script>
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
                        <h2>R$ <?= number_format($totais[$key]['a_receber'], 2, ',', '.') ?></h2>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card card-pagar p-3">
                        <h5>Contas a Pagar - <?= $label ?></h5>
                        <h2>R$ <?= number_format($totais[$key]['a_pagar'], 2, ',', '.') ?></h2>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-12">
                <div class="card new-card p-3">
                    <h5>Lançamentos a Receber</h5>
                    <ul>
                        <?php
                        $aReceberEntries = fetchContas($pdo, 'a_receber', "data BETWEEN '$inicioMes' AND '$hoje'");
                        foreach ($aReceberEntries as $entry):
                        ?>
                        <li>
                            <span><?= $entry['descricao'] ?></span>
                            <span><?= date('d/m/Y', strtotime($entry['data'])) ?></span>
                            <button 
                                id="status-a_receber-<?= $entry['id'] ?>" 
                                class="btn btn-status <?= $entry['status'] ?>" 
                                onclick="toggleStatus(<?= $entry['id'] ?>, '<?= $entry['status'] ?>', 'a_receber')">
                                <?= strtoupper($entry['status']) ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col-12">
                <div class="card new-card p-3">
                    <h5>Lançamentos a Pagar</h5>
                    <ul>
                        <?php
                        $aPagarEntries = fetchContas($pdo, 'a_pagar', "data BETWEEN '$inicioMes' AND '$hoje'");
                        foreach ($aPagarEntries as $entry):
                        ?>
                        <li>
                            <span><?= $entry['descricao'] ?></span>
                            <span><?= date('d/m/Y', strtotime($entry['data'])) ?></span>
                            <button 
                                id="status-a_pagar-<?= $entry['id'] ?>" 
                                class="btn btn-status <?= $entry['status'] ?>" 
                                onclick="toggleStatus(<?= $entry['id'] ?>, '<?= $entry['status'] ?>', 'a_pagar')">
                                <?= strtoupper($entry['status']) ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
