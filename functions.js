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

// Função para abrir modal de novo lançamento
window.openNewModal = function() {
    const newModal = document.getElementById('newModal');
    if (newModal) {
        newModal.style.display = 'block';
        document.getElementById('newData').value = new Date().toISOString().split('T')[0];
        document.getElementById('newValor').value = '0,00';
    }
}

// Função para fechar modal de novo lançamento
window.closeNewModal = function() {
    const newModal = document.getElementById('newModal');
    const newForm = document.getElementById('newForm');
    if (newModal) {
        newModal.style.display = 'none';
        if (newForm) newForm.reset();
    }
}

// Função para abrir modal de edição
window.openEditModal = async function(id, table) {
    const modal = document.getElementById('editModal');
    if (!modal) {
        console.error('Modal não encontrado');
        return;
    }

    try {
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

        const contentType = response.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Erro ao fazer parse do JSON:', text);
                throw new Error('Resposta não está no formato JSON esperado');
            }
        }

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

// Função para fechar modal de edição
window.closeModal = function() {
    const modal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');
    if (modal) {
        modal.style.display = 'none';
        if (editForm) editForm.reset();
    }
}

// Função para atualizar status
window.toggleStatus = async function(id, status, table) {
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
window.deleteLancamento = async function(id, table) {
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

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners para os botões de fechar modal
    const closeModalBtns = document.querySelectorAll('.modal-close');
    closeModalBtns.forEach(btn => {
        btn.onclick = closeModal;
    });

    // Fechar modais ao clicar fora
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        const newModal = document.getElementById('newModal');
        if (event.target == modal) {
            closeModal();
        }
        if (event.target == newModal) {
            closeNewModal();
        }
    }

    // Setup dos formulários
    const editForm = document.getElementById('editForm');
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

    const newForm = document.getElementById('newForm');
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

    // Adiciona máscaras de moeda aos inputs
    const inputs = document.querySelectorAll('input[name="valor"]');
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', function(e) {
                formatCurrency(e.target);
            });
        }
    });
});

// Função para filtrar por data
window.filtrarPorData = function(filtro) {
    let inicioMes, fimMes;

    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = hoje.getMonth();

    switch (filtro) {
        case 'atual':
            inicioMes = new Date(ano, mes, 1).toISOString().split('T')[0];
            fimMes = new Date(ano, mes + 1, 0).toISOString().split('T')[0];
            break;
        case 'anterior':
            inicioMes = new Date(ano, mes - 1, 1).toISOString().split('T')[0];
            fimMes = new Date(ano, mes, 0).toISOString().split('T')[0];
            break;
        case 'proximo':
            inicioMes = new Date(ano, mes + 1, 1).toISOString().split('T')[0];
            fimMes = new Date(ano, mes + 2, 0).toISOString().split('T')[0];
            break;
        default:
            inicioMes = new Date(ano, mes, 1).toISOString().split('T')[0];
            fimMes = new Date(ano, mes + 1, 0).toISOString().split('T')[0];
    }

    // Atualiza a URL com os parâmetros de filtro
    window.location.href = window.location.pathname + `?inicioMes=${inicioMes}&fimMes=${fimMes}`;
}