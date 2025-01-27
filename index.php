<!-- Arquivo index.php -->

<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zordin - Dashboard Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
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
                        <input type="text" id="editValor" name="valor" required oninput="formatCurrency(this)">
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


        <!-- Botão Novo Lançamento -->
<div class="text-center mb-4">
    <button class="btn-new" onclick="openNewModal()">Novo Lançamento</button>
</div>

<!-- Modal de Novo Lançamento -->
<div id="newModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5>Novo Lançamento</h5>
            <span class="modal-close" onclick="closeNewModal()">&times;</span>
        </div>
        <form id="newForm">
            <div class="form-group">
                <label for="newTipo">Tipo</label>
                <select id="newTipo" name="table" required>
                    <option value="a_receber">A Receber</option>
                    <option value="a_pagar">A Pagar</option>
                </select>
            </div>
            <div class="form-group">
                <label for="newDescricao">Descrição</label>
                <input type="text" id="newDescricao" name="descricao" required>
            </div>
            <div class="form-group">
                <label for="newValor">Valor</label>
                <input type="text" id="newValor" name="valor" required>
            </div>
            <div class="form-group">
                <label for="newData">Data</label>
                <input type="date" id="newData" name="data" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeNewModal()">Cancelar</button>
                <button type="submit" class="btn-save">Salvar</button>
            </div>
        </form>
    </div>
</div>

        <!-- Lançamentos a Receber -->
        <div class="card">
            <h5>Lançamentos a Receber</h5>
            <ul>
                <?php
                $aReceberEntries = $operations->fetchContas('a_receber', "data BETWEEN '$inicioMes' AND '$hoje'");
                foreach ($aReceberEntries as $entry):
                ?>
                <li>
                    <div class="content-wrapper">
                        <span class="description"><?= htmlspecialchars($entry['descricao']) ?></span>
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

        <!-- Lançamentos a Pagar -->
        <div class="card">
            <h5>Lançamentos a Pagar</h5>
            <ul>
                <?php
                $aPagarEntries = $operations->fetchContas('a_pagar', "data BETWEEN '$inicioMes' AND '$hoje'");
                foreach ($aPagarEntries as $entry):
                ?>
                <li>
                    <div class="content-wrapper">
                        <span class="description"><?= htmlspecialchars($entry['descricao']) ?></span>
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
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="functions.js"></script>
</body>
</html>