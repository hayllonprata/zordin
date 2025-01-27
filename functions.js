// Aguarda o DOM estar completamente carregado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa a aplicação
    initializeApp();
});

// Função de inicialização principal
function initializeApp() {
    // Inicializa os modais
    initializeModals();
    
    // Inicializa os formulários
    initializeForms();
    
    // Inicializa as máscaras de input
    initializeInputMasks();
}

// MANIPULAÇÃO DE MODAIS
// ====================

// Elementos dos modais
const modals = {
    edit: {
        modal: document.getElementById('editModal'),
        form: document.getElementById('editForm'),
        closeBtn: document.querySelector('#editModal .modal-close')
    },
    new: {
        modal: document.getElementById('newModal'),
        form: document.getElementById('newForm'),
        closeBtn: document.querySelector('#newModal .modal-close')
    }
};

function initializeModals() {
    // Configura os botões de fechar
    modals.edit.closeBtn?.addEventListener('click', () => closeModal('edit'));
    modals.new.closeBtn?.addEventListener('click', () => closeModal('new'));

    // Fecha modal ao clicar fora
    window.onclick = function(event) {
        if (event.target === modals.edit.modal) closeModal('edit');
        if (event.target === modals.new.modal) closeModal('new');
    };
}

// Funções de Modal de Edição
async function openEditModal(id, table) {
    try {
        const response = await fetchData('fetch', { id, table });
        if (!response) return;

        // Preenche o formulário
        document.getElementById('editId').value = response.id;
        document.getElementById('editTable').value = table;
        document.getElementById('editDescricao').value = response.descricao;
        document.getElementById('editValor').value = formatCurrencyForDisplay(response.valor);
        document.getElementById('editData').value = response.data;

        modals.edit.modal.style.display = 'block';
    } catch (error) {
        handleError('Erro ao carregar dados', error);
    }
}

// Funções de Modal Novo Lançamento
function openNewModal(table) {
    document.getElementById('newTable').value = table;
    document.getElementById('newData').value = new Date().toISOString().split('T')[0];
    document.getElementById('newValor').value = '';
    document.getElementById('newDescricao').value = '';
    modals.new.modal.style.display = 'block';
}

function closeModal(type) {
    modals[type].modal.style.display = 'none';
    modals[type].form.reset();
}

// MANIPULAÇÃO DE FORMULÁRIOS
// =========================

function initializeForms() {
    // Formulário de edição
    modals.edit.form?.addEventListener('submit', handleSubmit('edit'));
    
    // Formulário novo
    modals.new.form?.addEventListener('submit', handleSubmit('new'));
}

function handleSubmit(type) {
    return async function(e) {
        e.preventDefault();
        const form = e.target;
        
        try {
            const formData = new FormData(form);
            formData.append('action', type);
            
            // Processa o valor antes de enviar
            let valor = formData.get('valor');
            valor = parseCurrencyToFloat(valor);
            formData.set('valor', valor);

            await fetchData(type, Object.fromEntries(formData));
            closeModal(type);
            location.reload();
        } catch (error) {
            handleError(`Erro ao ${type === 'edit' ? 'atualizar' : 'criar'} lançamento`, error);
        }
    };
}

// MANIPULAÇÃO DE STATUS E DELEÇÃO
// ==============================

async function toggleStatus(id, status, table) {
    try {
        await fetchData('status', { id, status, table });
        location.reload();
    } catch (error) {
        handleError('Erro ao atualizar status', error);
    }
}

async function deleteLancamento(id, table) {
    if (!confirm('Tem certeza que deseja excluir este lançamento?')) return;

    try {
        await fetchData('delete', { id, table });
        location.reload();
    } catch (error) {
        handleError('Erro ao excluir lançamento', error);
    }
}

// FORMATAÇÃO DE MOEDA
// ==================

function initializeInputMasks() {
    // Adiciona máscaras em todos os inputs de valor
    const valorInputs = document.querySelectorAll('input[name="valor"]');
    valorInputs.forEach(input => {
        input.addEventListener('input', handleCurrencyInput);
    });
}

function handleCurrencyInput(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value === '') {
        e.target.value = '';
        return;
    }
    
    value = (parseInt(value) / 100).toFixed(2);
    e.target.value = formatCurrencyForDisplay(value);
}

function formatCurrencyForDisplay(value) {
    return parseFloat(value)
        .toFixed(2)
        .replace('.', ',')
        .replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
}

function parseCurrencyToFloat(value) {
    if (!value) return 0;
    return parseFloat(value.replace(/\./g, '').replace(',', '.'));
}

// UTILITÁRIOS
// ===========

async function fetchData(action, data = {}) {
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => formData.append(key, value));
    
    if (action !== 'fetch') {
        formData.append('action', action);
    }

    const response = await fetch('', {
        method: 'POST',
        body: formData
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    if (action === 'fetch') {
        return response.json();
    }

    return true;
}

function handleError(message, error) {
    console.error('Erro:', error);
    alert(`${message}. Por favor, tente novamente.`);
}