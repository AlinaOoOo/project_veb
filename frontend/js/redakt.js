document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = '⏳ Сохранение...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('./redakt.php', {
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
                successDiv.textContent = '✅ Данные успешно сохранены!';
                successDiv.className = 'success-message';
                
                const oldSuccess = document.querySelector('.success-message');
                if (oldSuccess) oldSuccess.remove();
                
                form.parentNode.insertBefore(successDiv, form);
                
                const oldErrors = document.querySelector('.error-messages');
                if (oldErrors) oldErrors.remove();
                
                successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (result.includes('Слишком') || 
                       result.includes('Номер') || 
                       result.includes('Введите') || 
                       result.includes('ФИО')) {
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid red;';
                errorDiv.innerHTML = result.replace(/\n/g, '<br>');
                errorDiv.className = 'error-messages';
                
                const oldErrors = document.querySelector('.error-messages');
                if (oldErrors) oldErrors.remove();
                
                form.parentNode.insertBefore(errorDiv, form);
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