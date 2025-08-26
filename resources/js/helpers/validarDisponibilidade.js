import dayjs from "dayjs";
import { pinia } from "../plugins/pinia.js";
import { useColaboradoresStore } from "@/stores/colaboradores.js";
import { useMaquinasStore } from "@/stores/maquinas.js";
import { HorarioIndisponivelError } from "@/errors/HorarioIndisponivelError.js";

const colaboradoresStore = useColaboradoresStore(pinia);
const maquinasStore = useMaquinasStore(pinia);

export const validarDisponibilidadeColaboradores = (colaboradores, inicio, fim, tarefaId = null) => {
    colaboradores.forEach((colaborador) => {
        const colaboradorComTarefas = colaboradoresStore.data.data.find((col) => {
            return col.id === colaborador.id;
        });

        if (tarefaId) {
            colaboradorComTarefas.tarefas = colaboradorComTarefas.tarefas.filter((tarefa) => tarefa.id !== tarefaId);
        }

        colaboradorComTarefas.tarefas.forEach((tarefa) => {
            const inicioExistente = new Date(tarefa.inicio);
            const fimExistente = new Date(tarefa.fim);

            const inicioNovo = dayjs(inicio);
            const fimNovo = dayjs(fim);

            const conflito = inicioNovo.isBefore(fimExistente, 'minute') &&
                             fimNovo.isAfter(inicioExistente, 'minute');

            if (conflito) {
                throw new HorarioIndisponivelError(
                    'Conflito',
                    `${colaborador.nome_completo} não está disponível para o período.`
                );
            }
        });
    });
}

export const validarDisponibilidadeMaquinas = (maquinas, inicio, fim, tarefaId = null) => {
    maquinas.forEach((maquina) => {
        const maquinaComTarefas = maquinasStore.data.data.find((col) => {
            return col.id === maquina.id;
        });

        if (tarefaId) {
            maquinaComTarefas.tarefas = maquinaComTarefas.tarefas.filter((tarefa) => tarefa.id !== tarefaId);
        }

        maquinaComTarefas.tarefas.forEach((tarefa) => {
            const inicioExistente = new Date(tarefa.inicio);
            const fimExistente = new Date(tarefa.fim);

            const inicioNovo = dayjs(inicio);
            const fimNovo = dayjs(fim);

            const conflito = inicioNovo.isBefore(fimExistente, 'minute') &&
                fimNovo.isAfter(inicioExistente, 'minute');

            if (conflito) {
                throw new HorarioIndisponivelError(
                    'Conflito',
                    `${maquina.nome} não está disponível para o período.`
                );
            }
        });
    });
}
