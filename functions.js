// Função para formatar moeda enquanto digita
function formatCurrency(input) {
    let value = input.value.replace(/\D/g, ''); // Remove tudo que não é número
    value = (parseFloat(value) / 100).toFixed(2); // Converte para decimal
    value = value.replace('.', ','); // Troca ponto por vírgula
    value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'); // Adiciona pontos
    input.value = value;
}

// Função para converter valor formatado para o formato do banco
function parseCurrencyToFloat(value) {
    return parseFloat(value.replace(/\./g, '').replace(',', '.'));
}

// Funções de Modal
const modal = document.getElementById('editModal');
const editForm = document.getElementById('editForm');
const closeModalBtn = document.querySelector('.modal-close');
const valorInput = document.getElementById('editValor');

// Adiciona a máscara de moeda ao input de valor
if (valorInput) {
    valorInput.addEventListener('input', function(e) {
        formatCurrency(e.target);
    });
}

// Função para abrir modal
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

        const data = await response.json();
        
        document.getElementById('editId').value = data.id;
        document.getElementById('editTable').value = table;
        document.getElementById('editDescricao').value = data.descricao;
        
        // Formata o valor para exibição
        const valorFormatado = parseFloat(data.valor).toFixed(2).replace('.', ',');
        document.getElementById('editValor').value = valorFormatado;
        
        document.getElementById('editData').value = data.data;

        modal.style.display = 'block';
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao carregar dados. Por favor, tente novamente.');
    }
}

// Função para fechar modal
function closeModal() {
    modal.style.display = 'none';
    editForm.reset();
}

// Evento de click no botão de fechar
if (closeModalBtn) {
    closeModalBtn.onclick = closeModal;
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target == modal) {
        closeModal();
    }
}

// Função para atualizar status
async function toggleStatus(id, status, table) {
    try {
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

            const response = await fetch('', {
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

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Verifica se existem elementos necessários
    if (!modal || !editForm) {
        console.error('Elementos necessários não encontrados!');
        return;
    }

    // Adiciona máscaras e eventos iniciais
    const inputs = document.querySelectorAll('input[name="valor"]');
    inputs.forEach(input => {
        input.addEventListener('input', function(e) {
            formatCurrency(e.target);
        });
    });
});