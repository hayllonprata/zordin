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


// Processa a exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['table'], $_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];
    $table = $_POST['table'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = :id AND user_id = '5511916674140'");
        $stmt->execute([':id' => $id]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Erro ao excluir: " . $e->getMessage();
        exit;
    }
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


// Adicione esta função junto com as outras funções PHP
function fetchLancamento($pdo, $table, $id) {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = :id AND user_id = '5511916674140'");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Adicione este bloco junto com os outros blocos de processamento POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch') {
    $id = (int)$_POST['id'];
    $table = $_POST['table'];
    
    try {
        $lancamento = fetchLancamento($pdo, $table, $id);
        header('Content-Type: application/json');
        echo json_encode($lancamento);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Erro ao buscar dados: " . $e->getMessage();
        exit;
    }
}

// Processa a edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $table = $_POST['table'];
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor']));
    $data = $_POST['data'];

    try {
        $stmt = $pdo->prepare("UPDATE $table SET descricao = :descricao, valor = :valor, data = :data WHERE id = :id AND user_id = '5511916674140'");
        $stmt->execute([
            ':id' => $id,
            ':descricao' => $descricao,
            ':valor' => $valor,
            ':data' => $data
        ]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Erro ao atualizar: " . $e->getMessage();
        exit;
    }
}
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
            --primary-purple: #8B5CF6;
            --dark-bg: #0A0A0A;
            --card-bg: rgba(23, 23, 23, 0.8);
            --text-color: #E5E7EB;
            --input-bg: rgba(17, 17, 17, 0.8);
        }
        body {
            background: linear-gradient(135deg, #121212, #1e1e1e);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
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

        /* Card de Recebimentos - Verde Premium */
        .card-receber {
            background: linear-gradient(135deg, #2c4c3b, #1a3327);
            border: 1px solid rgba(124, 184, 148, 0.2);
        }

        /* Card de Pagamentos - Vermelho Premium */
        .card-pagar {
            background: linear-gradient(135deg, #4c2c2c, #331a1a);
            border: 1px solid rgba(193, 122, 122, 0.2);
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-status {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            transition: all 0.2s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-status.pago {
            background: linear-gradient(135deg, #8B5CF6, #7c4ef0);
            color: white;
            box-shadow: 0 2px 4px rgba(139, 92, 246, 0.2);
        }

        .btn-status.nao-pago {
            background: linear-gradient(135deg, #c17a7a, #b36e6e);
            color: white;
            box-shadow: 0 2px 4px rgba(193, 122, 122, 0.2);
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
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        li:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.1);
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
            font-size: 1.1rem;
        }

        .date {
            font-size: 0.875rem;
            color: var(--text-color);
            opacity: 0.8;
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

        /* Efeitos Premium */
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .buttons-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 120px;
        }

        .btn-status, .btn-edit, .btn-delete {
            width: 100%;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            transition: all 0.2s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-status.pago {
            background: linear-gradient(135deg, #3CB770, #4BDC87);
            color: white;
            box-shadow: 0 2px 4px rgba(60, 183, 112, 0.2);
        }

        .btn-status.nao-pago {
            background: linear-gradient(135deg, #c17a7a, #b36e6e);
            color: white;
            box-shadow: 0 2px 4px rgba(193, 122, 122, 0.2);
        }

        .btn-edit {
            background: linear-gradient(135deg, #4A90E2, #5B9FE6);
            color: white;
            box-shadow: 0 2px 4px rgba(74, 144, 226, 0.2);
        }

        .btn-edit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3);
        }

        .btn-delete {
            background: #333;
            color: white;
        }

        .btn-delete:hover {
            background: #444;
            transform: translateY(-1px);
        }

        /* Modal de Edição */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background: var(--card-bg);
            margin: 15% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: var(--input-bg);
            color: var(--text-color);
        }

        .modal-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-save {
            background: linear-gradient(135deg, #3CB770, #4BDC87);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .btn-cancel {
            background: #333;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- [Todo o conteúdo HTML permanece igual, apenas atualizando as classes dos cards] -->
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
                <div class="card card-receber">
                    <h5>Contas a Receber - Hoje</h5>
                    <h2>R$ <?= number_format($totais['hoje']['a_receber'], 2, ',', '.') ?></h2>
                </div>
            </div>
            <div class="col-12">
                <div class="card card-pagar">
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

  <!-- Modal de Edição -->
  <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Editar Lançamento</h5>
                <span class="modal-close">&times;</span>
            </div>
            <form id="editForm">
                <input type="hidden" id="editId" name="id">
                <input type="hidden" id="editTable" name="table">
                <div class="form-group">
                    <label for="editDescricao">Descrição</label>
                    <input type="text" id="editDescricao" name="descricao" required>
                </div>
                <div class="form-group">
                    <label for="editValor">Valor</label>
                    <!-- No input de valor do modal -->
<input type="text" id="editValor" name="valor" required 
       onkeypress="return event.charCode >= 48 && event.charCode <= 57 || event.charCode === 44 || event.charCode === 46">
                </div>
                <div class="form-group">
                    <label for="editData">Data</label>
                    <input type="date" id="editData" name="data" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-save">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modificação na estrutura dos botões dentro dos cards -->
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
                <div class="buttons-wrapper">
                    <button 
                        id="status-a_receber-<?= $entry['id'] ?>" 
                        class="btn-status <?= str_replace(' ', '-', $entry['status']) ?>" 
                        onclick="toggleStatus(<?= $entry['id'] ?>, '<?= $entry['status'] ?>', 'a_receber')">
                        <?= strtoupper($entry['status']) ?>
                    </button>
                    <button 
                        class="btn-edit"
                        onclick="openEditModal(<?= $entry['id'] ?>, 'a_receber')">
                        EDITAR
                    </button>
                    <button 
                        class="btn-delete"
                        onclick="deleteLancamento(<?= $entry['id'] ?>, 'a_receber')">
                        EXCLUIR
                    </button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Repita a mesma estrutura para Lançamentos a Pagar -->
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
                <div class="buttons-wrapper">
                    <button 
                        id="status-a_pagar-<?= $entry['id'] ?>" 
                        class="btn-status <?= str_replace(' ', '-', $entry['status']) ?>" 
                        onclick="toggleStatus(<?= $entry['id'] ?>, '<?= $entry['status'] ?>', 'a_pagar')">
                        <?= strtoupper($entry['status']) ?>
                    </button>
                    <button 
                        class="btn-edit"
                        onclick="openEditModal(<?= $entry['id'] ?>, 'a_pagar')">
                        EDITAR
                    </button>
                    <button 
                        class="btn-delete"
                        onclick="deleteLancamento(<?= $entry['id'] ?>, 'a_pagar')">
                        EXCLUIR
                    </button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
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


        async function deleteLancamento(id, table) {
            if (!confirm('Tem certeza que deseja excluir este lançamento?')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('table', table);
                formData.append('action', 'delete');

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Erro ao excluir lançamento');
                }

                location.reload();
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir o lançamento. Por favor, tente novamente.');
            }
        }


async function openEditModal(id, table) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('table', table);
        formData.append('action', 'fetch');

        const response = await fetch('', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new TypeError("Oops, não recebemos JSON!");
        }

        const data = await response.json();
        if (!data) {
            throw new Error('Dados não encontrados');
        }
        
        document.getElementById('editId').value = data.id;
        document.getElementById('editTable').value = table;
        document.getElementById('editDescricao').value = data.descricao;
        document.getElementById('editValor').value = Number(data.valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        document.getElementById('editData').value = data.data;

        modal.style.display = 'block';
    } catch (error) {
        console.error('Erro detalhado:', error);
        alert('Erro ao carregar dados. Por favor, tente novamente.');
    }
}
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>