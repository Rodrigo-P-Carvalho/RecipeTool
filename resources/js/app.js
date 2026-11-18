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

const passwordResetForm = document.querySelector('[data-password-reset-form]');
const passwordRequirementForms = [passwordChangeForm, passwordResetForm].filter(Boolean);

passwordRequirementForms.forEach((form) => {
    const password = form.querySelector('#password');
    const confirmation = form.querySelector('#password_confirmation');
    const requirements = {
        length: () => Array.from(password.value).length >= 8,
        letters: () => /\p{L}/u.test(password.value),
        numbers: () => /\p{N}/u.test(password.value),
        confirmation: () => confirmation.value.length > 0,
        match: () => password.value.length > 0 && password.value === confirmation.value,
    };

    const updateRequirements = () => {
        Object.entries(requirements).forEach(([name, isComplete]) => {
            const requirement = form.querySelector(`[data-password-requirement="${name}"]`);
            const complete = isComplete();

            requirement.querySelector('[data-requirement-status]').textContent = complete ? '✓' : '○';
            requirement.classList.toggle('text-emerald-700', complete);
            requirement.classList.toggle('text-slate-500', !complete);
        });
    };

    password.addEventListener('input', updateRequirements);
    confirmation.addEventListener('input', updateRequirements);
    updateRequirements();
});

const productionForm = document.querySelector('[data-production-form]');

if (productionForm) {
    const recipeSelect = productionForm.querySelector('#receita_id');
    const batchCount = productionForm.querySelector('#quantidade_lotes');
    const availabilitySection = document.getElementById('disponibilidade-ingredientes');
    const availabilitySummary = document.getElementById('resumo-disponibilidade-ingredientes');
    const ingredientList = document.getElementById('lista-disponibilidade-ingredientes');
    const recipeAvailability = JSON.parse(
        document.getElementById('dados-disponibilidade-producao').textContent,
    );
    const quantityFormatter = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 });

    const formatQuantity = (quantity, baseUnit) => {
        let displayQuantity = quantity;
        let displayUnit = baseUnit;

        if (baseUnit === 'g' && quantity >= 1000) {
            displayQuantity = quantity / 1000;
            displayUnit = 'kg';
        } else if (baseUnit === 'ml' && quantity >= 1000) {
            displayQuantity = quantity / 1000;
            displayUnit = 'l';
        }

        return `${quantityFormatter.format(displayQuantity)} ${displayUnit}`;
    };

    const updateIngredientAvailability = () => {
        const recipeId = recipeSelect.value;
        const recipeItems = recipeAvailability[recipeId];

        availabilitySection.classList.toggle('hidden', !recipeId);
        ingredientList.replaceChildren();

        if (!recipeId) {
            return;
        }

        const numberOfBatches = batchCount.value === '' ? 0 : Number(batchCount.value);

        if (!Number.isInteger(numberOfBatches) || numberOfBatches < 0) {
            availabilitySummary.textContent = 'Informe uma quantidade válida de lotes para verificar o estoque.';
            return;
        }

        if (!recipeItems?.length) {
            availabilitySummary.textContent = 'Esta receita não possui ingredientes cadastrados.';
            return;
        }

        let allIngredientsAvailable = true;
        const hasProductionQuantity = numberOfBatches >= 1;

        recipeItems.forEach((item) => {
            const requiredQuantity = Math.round(item.quantidade_por_lote * numberOfBatches * 1000) / 1000;
            const isAvailable = item.quantidade_disponivel >= requiredQuantity;
            allIngredientsAvailable = allIngredientsAvailable && isAvailable;

            const row = document.createElement('li');
            row.className = 'flex flex-wrap items-center justify-between gap-2 py-3';

            const details = document.createElement('div');
            const name = document.createElement('p');
            name.className = 'text-sm font-medium text-slate-900';
            name.textContent = item.nome;

            const quantities = document.createElement('p');
            quantities.className = 'text-sm text-slate-600';
            quantities.textContent = `Necessário: ${formatQuantity(requiredQuantity, item.unidade_base)} · Disponível: ${formatQuantity(item.quantidade_disponivel, item.unidade_base)}`;

            const status = document.createElement('span');
            status.className = !hasProductionQuantity
                ? 'rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800'
                : isAvailable
                ? 'rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800'
                : 'rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800';
            status.textContent = !hasProductionQuantity
                ? 'Defina lotes'
                : isAvailable ? 'Suficiente' : 'Insuficiente';

            details.append(name, quantities);
            row.append(details, status);
            ingredientList.append(row);
        });

        availabilitySummary.textContent = !hasProductionQuantity
            ? 'Informe ao menos 1 lote para verificar se o estoque é suficiente.'
            : allIngredientsAvailable
                ? 'Há estoque válido suficiente para esta produção.'
                : 'Estoque válido insuficiente para produzir a quantidade selecionada.';
    };

    recipeSelect.addEventListener('change', updateIngredientAvailability);
    batchCount.addEventListener('input', () => {
        if (batchCount.value !== '' && Number(batchCount.value) < 0) {
            batchCount.value = '0';
        }

        updateIngredientAvailability();
    });
    updateIngredientAvailability();
}
