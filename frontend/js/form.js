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
            const response = await fetch('./index.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const result = await response.text();
            
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            
            if (result.includes('error-messages') || result.includes('❌')) {
                const oldErrors = document.querySelector('.error-messages');
                if (oldErrors) oldErrors.remove();
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-messages';
                errorDiv.style.cssText = 'background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid red;';
                errorDiv.innerHTML = result;
                
                form.parentNode.insertBefore(errorDiv, form);
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (result.includes('Спасибо') || result.includes('логин')) {
                const oldMessages = document.querySelector('.success-message');
                if (oldMessages) oldMessages.remove();
                
                const successDiv = document.createElement('div');
                successDiv.className = 'success-message';
                successDiv.innerHTML = result;
                
                form.parentNode.insertBefore(successDiv, form);
                form.reset();
                
                const oldErrors = document.querySelector('.error-messages');
                if (oldErrors) oldErrors.remove();
                
                successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
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