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
    $sql = "SELECT * FROM $table WHERE $filter AND user_id = '5511916674140' ORDER BY data ASC";
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

// filtros para dia e mês
$hoje = date('Y-m-d');
$inicioMes = date('Y-m-01');
$totais = [
    'hoje' => [
        'a_receber' => calcularTotal($pdo, 'a_receber', "data = '$hoje'"),
        'a_pagar' => calcularTotal($pdo, 'a_pagar', "data = '$hoje'"),
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
        :root {
            --primary-green: #28a745;
            --dark-bg: #121212;
            --card-bg: rgba(23, 23, 23, 0.8);
            --text-color: #FFFFFF;
            --input-bg: rgba(17, 17, 17, 0.8);
        }

        body {
            background: linear-gradient(135deg, #121212, #1e1e1e);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            background-color: #000;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--text-color);
            text-align: center;
            margin-bottom: 3rem;
        }

        h2 {
            color: var(--text-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        h5 {
            color: var(--text-color);
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .btn-status {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-status.pago {
            background-color: var(--primary-green);
            color: white;
        }

        .btn-status.nao-pago {
            background-color: #dc3545;
            color: white;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        li:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .content-wrapper {
            flex-grow: 1;
            margin-right: 1rem;
        }

        .description {
            display: block;
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .valor {
            display: block;
            color: var(--text-color);
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .date {
            font-size: 0.875rem;
            color: var(--text-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            h1 {
                font-size: 2rem;
                margin-bottom: 2rem;
            }

            .card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Zordin - Sistema Financeiro</h1>

        <!-- Saldo -->
        <div class="card">
            <h5>Saldo em Conta</h5>
            <h2 class="text-success">R$ <?= number_format($saldo, 2, ',', '.') ?></h2>
        </div>

        <!-- Totais do dia -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5>Contas a Receber - Hoje</h5>
                    <h2>R$ <?= number_format($totais['hoje']['a_receber'], 2, ',', '.') ?></h2>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <h5>Contas a Pagar - Hoje</h5>
                    <h2>R$ <?= number_format($totais['hoje']['a_pagar'], 2, ',', '.') ?></h2>
                </div>
            </div>
        </div>

        <!-- Totais do mês -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <h5>Contas a Receber - Mês</h5>
                    <h2>R$ <?= number_format($totais['mes']['a_receber'], 2, ',', '.') ?></h2>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <h5>Contas a Pagar - Mês</h5>
                    <h2>R$ <?= number_format($totais['mes']['a_pagar'], 2, ',', '.') ?></h2>
                </div>
            </div>
        </div>

        <!-- Lançamentos a Receber -->
        <div class="card">
            <h5>Lançamentos a Receber</h5>
            <ul>
                <?php
                $aReceberEntries = fetchContas($pdo, 'a_receber', "data BETWEEN '$inicioMes' AND '$hoje'");
                foreach ($aReceberEntries as $entry):
                ?>
                <li>
                    <div class="content-wrapper">
                        <span class="description"><?= $entry['descricao'] ?></span>
                        <span class="valor">R$ <?= number_format($entry['valor'], 2, ',', '.') ?></span>
                        <span class="date"><?= date('d/m/Y', strtotime($entry['data'])) ?></span>
                    </div>
                    <button 
                        id="status-a_receber-<?= $entry['id'] ?>" 
                        class="btn-status <?= str_replace(' ', '-', $entry['status']) ?>" 
                        onclick="toggleStatus(<?= $entry['id'] ?>, '<?= $entry['status'] ?>', 'a_receber')">
                        <?= strtoupper($entry['status']) ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Lançamentos a Pagar -->
        <div class="card">
            <h5>Lançamentos a Pagar</h5>
            <ul>
                <?php
                $aPagarEntries = fetchContas($pdo, 'a_pagar', "data BETWEEN '$inicioMes' AND '$hoje'");
                foreach ($aPagarEntries as $entry):
                ?>
                <li>
                    <div class="content-wrapper">
                        <span class="description"><?= $entry['descricao'] ?></span>
                        <span class="valor">R$ <?= number_format($entry['valor'], 2, ',', '.') ?></span>
                        <span class="date"><?= date('d/m/Y', strtotime($entry['data'])) ?></span>
                    </div>
                    <button 
                        id="status-a_pagar-<?= $entry['id'] ?>" 
                        class="btn-status <?= str_replace(' ', '-', $entry['status']) ?>" 
                        onclick="toggleStatus(<?= $entry['id'] ?>, '<?= $entry['status'] ?>', 'a_pagar')">
                        <?= strtoupper($entry['status']) ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
        async function toggleStatus(id, status, table) {
            try {
                const newStatus = status === 'pago' ? 'nao pago' : 'pago';
                const formData = new FormData();
                formData.append('id', id);
                formData.append('status', status);
                formData.append('table', table);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Erro ao atualizar status');
                }

                const button = document.getElementById(`status-${table}-${id}`);
                if (button) {
                    button.className = `btn-status ${newStatus.replace(' ', '-')}`;
                    button.innerText = newStatus.toUpperCase();
                    button.setAttribute('onclick', `toggleStatus(${id}, '${newStatus}', '${table}')`);
                }

                location.reload();
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao atualizar o status. Por favor, tente novamente.');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
