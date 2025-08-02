export function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

export function normalizeCpf(cpf) {
    return parseInt(cpf.replace(/\D/g, ''));
}

export function formatCpf(cpf) {
    const digits = normalizeCpf(cpf);

    if (digits.length !== 11) return cpf;

    return digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}
