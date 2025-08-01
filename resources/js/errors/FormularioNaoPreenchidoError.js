export class FormularioNaoPreenchidoError extends Error {
    constructor(titulo, mensagem) {
        super(mensagem);
        this.name = "HorarioIndisponivelError";
        this.title = titulo;
    }
}
