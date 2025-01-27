// Função para abrir modal de edição
async function openEditModal(id, table) {
    const modal = document.getElementById('editModal');
    if (!modal) {
        console.error('Modal não encontrado');
        return;
    }

    try {
        console.log('Iniciando abertura do modal para:', { id, table }); // Debug log

        const formData = new FormData();
        formData.append('id', id);
        formData.append('table', table);
        formData.append('action', 'fetch');

        // Log dos dados sendo enviados
        console.log('Dados sendo enviados:', {
            id: formData.get('id'),
            table: formData.get('table'),
            action: formData.get('action')
        });

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json' // Adiciona header específico para JSON
            }
        });

        // Log da resposta
        console.log('Status da resposta:', response.status);
        console.log('Headers da resposta:', response.headers);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Resposta do servidor:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            console.error('Resposta não é JSON:', contentType);
            throw new Error('Resposta do servidor não é JSON válido');
        }

        const data = await response.json();
        console.log('Dados recebidos:', data); // Debug log

        if (!data) {
            throw new Error('Dados não encontrados');
        }
        
        // Atualiza os campos do formulário
        const fields = ['editId', 'editTable', 'editDescricao', 'editData'];
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (!element) {
                console.error(`Campo ${field} não encontrado`);
                return;
            }
        });

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
        console.error('Erro detalhado:', error);
        alert('Erro ao carregar dados. Por favor, tente novamente. Detalhes no console.');
    }
}

// Verifica se o PHP está retornando os dados corretamente
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona listener para o formulário de edição
    const editForm = document.getElementById('editForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(editForm);
                formData.append('action', 'edit');
                
                // Log dos dados antes do envio
                console.log('Dados do formulário:', {
                    id: formData.get('id'),
                    table: formData.get('table'),
                    descricao: formData.get('descricao'),
                    valor: formData.get('valor'),
                    data: formData.get('data')
                });

                // Converte o valor formatado para o formato do banco
                let valor = formData.get('valor');
                valor = parseCurrencyToFloat(valor);
                formData.set('valor', valor);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Erro na resposta:', errorText);
                    throw new Error('Erro ao atualizar lançamento');
                }

                closeModal();
                location.reload();
            } catch (error) {
                console.error('Erro detalhado:', error);
                alert('Erro ao atualizar o lançamento. Por favor, tente novamente.');
            }
        });
    }
});