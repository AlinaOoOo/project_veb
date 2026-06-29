document.addEventListener('DOMContentLoaded', function() {
    // Обработка выбора пользователя
    const selectForm = document.querySelector('form');
    if (selectForm && selectForm.querySelector('select[name="user_id"]')) {
        selectForm.addEventListener('submit', function(e) {
            // Это обычная форма, она будет перезагружать страницу - так и должно быть
            // для выбора пользователя
        });
    }
    
    // Обработка формы редактирования
    const editForm = document.querySelector('form[action="./admin_edit_back.php"]');
    if (!editForm) return;
    
    editForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(editForm);
        
        const submitBtn = editForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = '⏳ Сохранение...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('./admin_edit_back.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const result = await response.text();
            const trimmedResult = result.trim();
            
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            if (trimmedResult === 'success') {
                const successDiv = document.createElement('div');
                successDiv.style.cssText = 'background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; color: #155724;';
                successDiv.textContent = '✅ Данные пользователя успешно обновлены!';
                successDiv.className = 'success-message';
                
                const oldSuccess = document.querySelector('.success-message');
                if (oldSuccess) oldSuccess.remove();
                
                editForm.parentNode.insertBefore(successDiv, editForm);
                
                const oldErrors = document.querySelector('.error-messages');
                if (oldErrors) oldErrors.remove();
                
                successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (result.includes('Ошибка') || 
                       result.includes('Нельзя') || 
                       result.includes('Не указан')) {
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid red;';
                errorDiv.textContent = result;
                errorDiv.className = 'error-messages';
                
                const oldErrors = document.querySelector('.error-messages');
                if (oldErrors) oldErrors.remove();
                
                editForm.parentNode.insertBefore(errorDiv, editForm);
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                console.log('Неизвестный ответ:', result);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            alert('Произошла ошибка при отправке формы');
        }
    });
});