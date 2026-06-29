document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = '⏳ Вход...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('./autorization.php', {
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
                window.location.href = './redakt.php';
            } else {
                const oldErrors = document.querySelector('.auth-error');
                if (oldErrors) oldErrors.remove();
                
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = 'background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid red; color: red;';
                errorDiv.textContent = trimmedResult || 'Неверный логин или пароль';
                errorDiv.className = 'auth-error';
                
                form.parentNode.insertBefore(errorDiv, form);
                
                const passwordInput = form.querySelector('input[name="password"]');
                if (passwordInput) passwordInput.value = '';
            }
        } catch (error) {
            console.error('Ошибка:', error);
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            alert('Произошла ошибка при отправке формы');
        }
    });
});