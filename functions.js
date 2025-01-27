// Inicialização dos elementos do modal
document.addEventListener('DOMContentLoaded', function() {
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
    window.openEditModal = async function(id, table) {
        const modal = document.getElementById('editModal');
        if (!modal) {
            console.error('Modal não encontrado');
            return;
        }

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

    // Função para fechar modal de edição
    window.closeModal = function() {
        const modal = document.getElementById('editModal');
        if (modal) {
            modal.style.display = 'none';
            if (editForm) editForm.reset();
        }
    }

    // Evento de click no botão de fechar
    if (closeModalBtn) {
        closeModalBtn.onclick = window.closeModal;
    }

    // Fechar modal ao clicar fora
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        const newModal = document.getElementById('newModal');
        if (event.target == modal) {
            window.closeModal();
        }
        if (event.target == newModal) {
            window.closeNewModal();
        }
    }
});