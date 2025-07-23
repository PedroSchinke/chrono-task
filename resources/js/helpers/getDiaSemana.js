export const getDiaSemana = (index) => {
    switch (index) {
        case 0:
            return { label: 'Domingo', name: 'domingo'};
        case 1:
            return { label: 'Segunda', name: 'segunda'};
        case 2:
            return { label: 'Terça', name: 'terca'};
        case 3:
            return { label: 'Quarta', name: 'quarta'};
        case 4:
            return { label: 'Quinta', name: 'quinta'};
        case 5:
            return { label: 'Sexta', name: 'sexta'};
        case 6:
            return { label: 'Sábado', name: 'sabado'};
    }
}
