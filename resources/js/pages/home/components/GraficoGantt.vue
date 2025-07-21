<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";
import dayjs from "dayjs";
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import Toast from "primevue/toast";
import Select from 'primevue/select';
import Button from "primevue/button";
import AdicionarTarefa from "./AdicionarTarefa.vue";
import LinhaGantt from './LinhaGantt.vue';

dayjs.extend(isSameOrBefore);

const toast = useToast();

const props = defineProps(['maquinas' , 'horariosDisponiveis']);
const emit = defineEmits(['recarregarMaquinas']);

const adicionarTarefaDialog = ref(null);

const qtdDiasExibidos = ref({
    dias: 14,
    label: '2 semanas'
});

const qtdDiasOption = [
    {
        dias: 7,
        label: '1 semana'
    },
    {
        dias: 14,
        label: '2 semanas'
    },
    {
        dias: 31,
        label: '1 mês'
    },
    {
        dias: 61,
        label: '2 meses'
    },
    {
        dias: 185,
        label: '6 meses'
    },
    {
        dias: 365,
        label: '1 ano'
    },
    {
        dias: 730,
        label: '2 anos'
    },
];

const dias = computed(() => {
    return getDates(dayjs().startOf('day'), dayjs().add(qtdDiasExibidos.value.dias, 'day'));
});

function getDates(startDate, stopDate) {
    const dateArray = [];
    let currentDate = startDate;

    while (currentDate.isSameOrBefore(stopDate)) {
        dateArray.push(currentDate);
        currentDate = currentDate.add(1, 'day');
    }

    return dateArray;
}

const reposicionarTarefa = async ({ tarefa, deslocamento, posY }) => {
    let idMaquina = tarefa.id_maquina;
    let deslocamentoMaquina = Math.trunc(posY / 60);

    if (deslocamentoMaquina !== 0) {
        const maquinaIndex = props.maquinas.findIndex((maquina) => {
            return maquina.id === tarefa.id_maquina;
        });

        idMaquina = props.maquinas[maquinaIndex + deslocamentoMaquina].id;
    }

    const novaDataInicio = dayjs(tarefa.inicio).add(deslocamento, 'day');
    const novaDataFim = dayjs(tarefa.fim).add(deslocamento, 'day');

    const diasReposicionamento = getDates(novaDataInicio, novaDataFim);

    try {
        validarHorarios(tarefa, diasReposicionamento, idMaquina);

        tarefa.inicio = novaDataInicio.format('YYYY-MM-DD HH:mm:ss');
        tarefa.fim = novaDataFim.format('YYYY-MM-DD HH:mm:ss');

        await api.post(`/tarefa/${tarefa.id}`, {
            inicio: novaDataInicio.format('YYYY-MM-DD'),
            fim: novaDataFim.format('YYYY-MM-DD'),
            id_maquina: idMaquina
        });
    } catch (e) {
        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: e.message, life: 3000 });
        }

        return;
    }

    recarregarMaquinas();
}

const adicionarTarefa = () => {
    adicionarTarefaDialog.value.openDialog();
}

const recarregarMaquinas = () => {
    emit('recarregarMaquinas');
}

class HorarioIndisponivelError extends Error {
    constructor(titulo, mensagem) {
        super(mensagem);
        this.name = "HorarioIndisponivelError";
        this.title = titulo;
    }
}

const validarHorarios = (tarefa, diasReposicionamento, idMaquina) => {
    const horariosDisponiveisDaMaquina = props.horariosDisponiveis.filter((horario) => {
        return horario.id_maquina == idMaquina;
    });

    diasReposicionamento.forEach((dia) => {
        const diaSemana = getDiaSemana(dia.day()).name;

        const disponivel = horariosDisponiveisDaMaquina.some((horario) => {
            return diaSemana === horario.dia_semana &&
                   tarefa.periodo_diario_inicio >= horario.hora_inicio &&
                   tarefa.periodo_diario_fim <= horario.hora_fim;
        });

        if (!disponivel) {
            throw new HorarioIndisponivelError('Não foi possível mover tarefa', 'Horário indisponível');
        }
    });
}

const getDiaSemana = (index) => {
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
</script>

<template>
    <Toast />

    <AdicionarTarefa ref="adicionarTarefaDialog" @recarregar-tarefas="recarregarMaquinas()" />

    <header>
        <h1>Tarefas</h1>

        <div>
            <Select
                v-model="qtdDiasExibidos"
                :options="qtdDiasOption"
                option-label="label"
                placeholder="Selecione o período"
                :default-value="qtdDiasExibidos"
                checkmark
                :highlightOnSelect="false"
            />

            <Button title="Adicionar Tarefa" icon="pi pi-plus" rounded @click="adicionarTarefa()" />
        </div>
    </header>

    <div class="gantt-chart">
        <div class="gantt-header" :style="`grid-template-columns: 200px repeat(${qtdDiasExibidos.dias + 1}, 100px)`">
            <div class="task-label-header">Máquina</div>
            <div v-for="dia in dias" :key="dia" class="day-header">
                <span>{{ dia.format('DD/MM') }}</span>

                <span style="font-weight: normal">
                    {{ getDiaSemana(dia.day()).label }}
                </span>
            </div>
        </div>

        <LinhaGantt
            v-for="maquina in maquinas"
            :key="maquina.id"
            :maquina="maquina"
            :dias="dias"
            @reposicionar="(data) => reposicionarTarefa(data)"
        />
    </div>
</template>

<style scoped>
header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

header div {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.gantt-chart {
    overflow-x: auto;
    white-space: nowrap;
}

.gantt-header {
    min-width: fit-content;
    display: grid;
    font-weight: bold;
    background: #18181b;
    border-bottom: 1px solid #27272a;
}

.task-label-header {
    padding: 10px;
    display: flex;
    align-items: center;
    border-right: 1px solid #27272a;
    background: #18181b;
}

.day-header {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 10px 0;
    border-left: 1px solid #27272a;
}
</style>

