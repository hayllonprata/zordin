<?php
// Database configuration
$config = [
    'host' => 'databases_zordin',
    'port' => '5432',
    'dbname' => 'databases',
    'user' => 'postgres',
    'password' => '55081546289173748df1'
];

// Database connection
function connectDB($config) {
    try {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
        $pdo = new PDO($dsn, $config['user'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro na conexão: " . $e->getMessage() . " (" . $e->getCode() . ")");
    }
}

$pdo = connectDB($config);
$USER_ID = '5511916674140'; // Constant user ID

// CRUD Operations
class FinancialOperations {
    private $pdo;
    private $userId;

    public function __construct($pdo, $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    public function deleteLancamento($id, $table) {
        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE id = :id AND user_id = :userId");
        return $stmt->execute([':id' => $id, ':userId' => $this->userId]);
    }

    public function updateStatus($id, $table, $status) {
        $newStatus = $status === 'pago' ? 'nao pago' : 'pago';
        $stmt = $this->pdo->prepare("UPDATE $table SET status = :status WHERE id = :id AND user_id = :userId");
        return $stmt->execute([':status' => $newStatus, ':id' => $id, ':userId' => $this->userId]);
    }

    public function fetchLancamento($table, $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE id = :id AND user_id = :userId");
        $stmt->execute([':id' => $id, ':userId' => $this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateLancamento($table, $id, $data) {
        $stmt = $this->pdo->prepare(
            "UPDATE $table 
            SET descricao = :descricao, 
                valor = :valor, 
                data = :data 
            WHERE id = :id AND user_id = :userId"
        );
        
        return $stmt->execute([
            ':id' => $id,
            ':descricao' => $data['descricao'],
            ':valor' => $data['valor'],
            ':data' => $data['data'],
            ':userId' => $this->userId
        ]);
    }

    public function createLancamento($table, $data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO $table 
            (descricao, valor, data, status, user_id) 
            VALUES 
            (:descricao, :valor, :data, 'nao pago', :userId)"
        );
        
        return $stmt->execute([
            ':descricao' => $data['descricao'],
            ':valor' => (float)$data['valor'],
            ':data' => $data['data'],
            ':userId' => $this->userId
        ]);
    }

    public function fetchContas($table, $filter) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM $table 
            WHERE $filter AND user_id = :userId 
            ORDER BY data ASC, id ASC"
        );
        $stmt->execute([':userId' => $this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calcularSaldo() {
        $inicioMes = date('Y-m-01');
        $fimMes = date('Y-m-t');
        
        // Calcula total de recebimentos do mês
        $recebimentos = $this->pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) as total 
            FROM a_receber 
            WHERE user_id = :userId 
            AND data BETWEEN :inicio AND :fim"
        );
        $recebimentos->execute([
            ':userId' => $this->userId,
            ':inicio' => $inicioMes,
            ':fim' => $fimMes
        ]);
        $totalRecebimentos = $recebimentos->fetch()['total'];
        
        // Calcula total de pagamentos do mês
        $pagamentos = $this->pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) as total 
            FROM a_pagar 
            WHERE user_id = :userId 
            AND data BETWEEN :inicio AND :fim"
        );
        $pagamentos->execute([
            ':userId' => $this->userId,
            ':inicio' => $inicioMes,
            ':fim' => $fimMes
        ]);
        $totalPagamentos = $pagamentos->fetch()['total'];

        // Retorna recebimentos - pagamentos
        return $totalRecebimentos - $totalPagamentos;
    }

    public function calcularTotal($table, $filter) {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(valor), 0) as total 
            FROM $table 
            WHERE $filter AND user_id = :userId"
        );
        $stmt->execute([':userId' => $this->userId]);
        return $stmt->fetch()['total'];
    }
}

// Initialize operations
$operations = new FinancialOperations($pdo, $USER_ID);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action'] ?? '') {
            case 'delete':
                if (isset($_POST['id'], $_POST['table'])) {
                    $operations->deleteLancamento((int)$_POST['id'], $_POST['table']);
                    $response['success'] = true;
                }
                break;

            case 'fetch':
                if (isset($_POST['id'], $_POST['table'])) {
                    $lancamento = $operations->fetchLancamento($_POST['table'], (int)$_POST['id']);
                    echo json_encode($lancamento);
                    exit;
                }
                break;

            case 'edit':
                if (isset($_POST['id'], $_POST['table'])) {
                    $operations->updateLancamento(
                        $_POST['table'],
                        (int)$_POST['id'],
                        [
                            'descricao' => $_POST['descricao'],
                            'valor' => (float)$_POST['valor'],
                            'data' => $_POST['data']
                        ]
                    );
                    $response['success'] = true;
                }
                break;

            case 'new':
                if (isset($_POST['table'], $_POST['descricao'], $_POST['valor'], $_POST['data'])) {
                    $operations->createLancamento(
                        $_POST['table'],
                        [
                            'descricao' => $_POST['descricao'],
                            'valor' => (float)$_POST['valor'],
                            'data' => $_POST['data']
                        ]
                    );
                    $response['success'] = true;
                }
                break;

            default:
                if (isset($_POST['id'], $_POST['status'], $_POST['table'])) {
                    $operations->updateStatus((int)$_POST['id'], $_POST['table'], $_POST['status']);
                    $response['success'] = true;
                }
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        http_response_code(500);
    }

    if (!empty($response['message']) || isset($_POST['action'])) {
        echo json_encode($response);
        exit;
    }
}

// Verifica se há parâmetros de filtro na URL
$inicioMes = $_GET['inicioMes'] ?? date('Y-m-01');
$fimMes = $_GET['fimMes'] ?? date('Y-m-t');

// Calcula totais para exibição
$hoje = date('Y-m-d');
$totais = [
    'hoje' => [
        'a_receber' => $operations->calcularTotal('a_receber', "data = '$hoje'"),
        'a_pagar' => $operations->calcularTotal('a_pagar', "data = '$hoje'"),
    ],
    'mes' => [
        'a_receber' => $operations->calcularTotal('a_receber', "data BETWEEN '$inicioMes' AND '$fimMes' AND status = 'pago'"),
        'a_pagar' => $operations->calcularTotal('a_pagar', "data BETWEEN '$inicioMes' AND '$fimMes' AND status = 'pago'"),
    ]
];

$saldo = $operations->calcularSaldo();
?>