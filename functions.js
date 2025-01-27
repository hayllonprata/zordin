// Função para formatar moeda enquanto digita
function formatCurrency(input) {
    let value = input.value.replace(/\D/g, ''); // Remove tudo que não é número
    if (value === '') {
        input.value = '0,00';
        return;
    }
    value = (parseFloat(value) / 100).toFixed(2); // Converte para decimal
    value = value.replace('.', ','); // Troca ponto por vírgula
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'); // Adiciona pontos
    input.value = value;
}

// Função para converter valor formatado para o formato do banco
function parseCurrencyToFloat(value) {
    if (!value) return 0;
    return parseFloat(value.replace(/\./g, '').replace(',', '.'));
}

// Inicialização dos elementos do modal
const modal = document.getElementById('editModal');
const editForm = document.getElementById('editForm');
const closeModalBtn = modal ? modal.querySelector('.modal-close') : null;
const valorInput = document.getElementById('editValor');
const newModal = document.getElementById('newModal');
const newForm = document.getElementById('newForm');
const newValorInput = document.getElementById('newValor');

// Adiciona a máscara de moeda aos inputs de valor
[valorInput, newValorInput].forEach(input => {
    if (input) {
        input.addEventListener('input', function(e) {
            formatCurrency(e.target);
        });
    }
});

// Função para abrir modal de edição
async function openEditModal(id, table) {
    try {
        console.log('Abrindo modal para:', id, table); // Debug log
        const formData = new FormData();
        formData.append('id', id);
        formData.append('table', table);
        formData.append('action', 'fetch');

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data) {
            throw new Error('Dados não encontrados');
        }
        
        document.getElementById('editId').value = data.id;
        document.getElementById('editTable').value = table;
        document.getElementById('editDescricao').value = data.descricao;
        
        // Formata o valor para exibição
        if (data.valor) {
            const valor = parseFloat(data.valor);
            const valorFormatado = valor.toFixed(2).replace('.', ',');
            document.getElementById('editValor').value = valorFormatado;
        }
        
        document.getElementById('editData').value = data.data;
        modal.style.display = 'block';
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao carregar dados. Por favor, tente novamente.');
    }
}

// Função para abrir modal de novo lançamento
function openNewModal() {
    if (newModal) {
        newModal.style.display = 'block';
        document.getElementById('newData').value = new Date().toISOString().split('T')[0];
        document.getElementById('newValor').value = '0,00';
    }
}

// Função para fechar modal de edição
function closeModal() {
    if (modal) {
        modal.style.display = 'none';
        editForm.reset();
    }
}

// Função para fechar modal de novo lançamento
function closeNewModal() {
    if (newModal) {
        newModal.style.display = 'none';
        newForm.reset();
    }
}

// Eventos de click nos botões de fechar
if (closeModalBtn) {
    closeModalBtn.onclick = closeModal;
}

// Fechar modais ao clicar fora
window.onclick = function(event) {
    if (event.target == modal) {
        closeModal();
    }
    if (event.target == newModal) {
        closeNewModal();
    }
}

// Função para atualizar status
async function toggleStatus(id, status, table) {
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        formData.append('table', table);

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Erro ao atualizar status');
        }

        location.reload();
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao atualizar o status. Por favor, tente novamente.');
    }
}

// Função para deletar lançamento
async function deleteLancamento(id, table) {
    if (!confirm('Tem certeza que deseja excluir este lançamento?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('table', table);
        formData.append('action', 'delete');

        const response = await fetch(window.location.href, {
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

// Manipulação do formulário de edição
if (editForm) {
    editForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(editForm);
            formData.append('action', 'edit');
            
            // Converte o valor formatado para o formato do banco
            let valor = formData.get('valor');
            valor = parseCurrencyToFloat(valor);
            formData.set('valor', valor);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erro ao atualizar lançamento');
            }

            closeModal();
            location.reload();
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao atualizar o lançamento. Por favor, tente novamente.');
        }
    });
}

// Manipulação do formulário de novo lançamento
if (newForm) {
    newForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(newForm);
            formData.append('action', 'new');
            
            // Converte o valor formatado para o formato do banco
            let valor = formData.get('valor');
            valor = parseCurrencyToFloat(valor);
            formData.set('valor', valor);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Erro ao criar lançamento');
            }

            closeNewModal();
            location.reload();
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao criar o lançamento. Por favor, tente novamente.');
        }
    });
}

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona máscaras e eventos iniciais
    const inputs = document.querySelectorAll('input[name="valor"]');
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function(e) {
                formatCurrency(e.target);
            });
        }
    });
});