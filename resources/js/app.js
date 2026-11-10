const loginForm = document.querySelector('[data-login-form]');

if (loginForm) {
    const emailInput = loginForm.querySelector('#email');
    const rememberInput = loginForm.querySelector('#remember');
    const rememberedEmailKey = 'recipetool.remembered-email';
    const rememberedEmail = localStorage.getItem(rememberedEmailKey);

    if (rememberedEmail && !emailInput.value) {
        emailInput.value = rememberedEmail;
        rememberInput.checked = true;
    }

    rememberInput.addEventListener('change', () => {
        if (!rememberInput.checked) {
            localStorage.removeItem(rememberedEmailKey);
        }
    });

    loginForm.addEventListener('submit', () => {
        const email = emailInput.value.trim();

        if (rememberInput.checked && email) {
            localStorage.setItem(rememberedEmailKey, email);
        } else {
            localStorage.removeItem(rememberedEmailKey);
        }
    });
}

const passwordChangeForm = document.querySelector('[data-password-change-form]');

if (passwordChangeForm) {
    const password = passwordChangeForm.querySelector('#password');
    const confirmation = passwordChangeForm.querySelector('#password_confirmation');
    const requirements = {
        length: () => Array.from(password.value).length >= 8,
        confirmation: () => confirmation.value.length > 0,
        match: () => password.value.length > 0 && password.value === confirmation.value,
    };

    const updateRequirements = () => {
        Object.entries(requirements).forEach(([name, isComplete]) => {
            const requirement = passwordChangeForm.querySelector(`[data-password-requirement="${name}"]`);
            const complete = isComplete();

            requirement.querySelector('[data-requirement-status]').textContent = complete ? '✓' : '○';
            requirement.classList.toggle('text-emerald-700', complete);
            requirement.classList.toggle('text-slate-500', !complete);
        });
    };

    password.addEventListener('input', updateRequirements);
    confirmation.addEventListener('input', updateRequirements);
    updateRequirements();
}
