<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from "primevue/usetoast";
import api from "@/axios.js";
import dayjs from "dayjs";
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import Toast from "primevue/toast";
import Button from "primevue/button";
import AdicionarTarefa from "./AdicionarTarefa.vue";
import LinhaGantt from './LinhaGantt.vue';

dayjs.extend(isSameOrBefore);

const toast = useToast();

const props = defineProps(['maquinas']);
const emit = defineEmits(['recarregarMaquinas']);

const horariosDisponiveis = ref([]);

const adicionarTarefaDialog = ref(null);

const qtdDiasExibidos = ref(60);
const dias = getDates(dayjs().startOf('day'), dayjs().add(qtdDiasExibidos.value, 'day'));

function getDates(startDate, stopDate) {
    const dateArray = [];
    let currentDate = startDate;

    while (currentDate.isSameOrBefore(stopDate)) {
        dateArray.push(currentDate);
        currentDate = currentDate.add(1, 'day');
    }

    return dateArray;
}

onMounted(() => {
    getHorariosDisponiveis();
});

const getHorariosDisponiveis = async () => {
    try {
        const resp = await api.get('/horarios-disponiveis');

        horariosDisponiveis.value = resp.data.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível buscar horários disponíveis', life: 3000 });
    }
}

const reposicionarTarefa = async ({ tarefa, deslocamento }) => {
    const novaDataInicio = dayjs(tarefa.inicio).add(deslocamento, 'day');
    const novaDataFim = dayjs(tarefa.fim).add(deslocamento, 'day');

    const diasReposicionamento = getDates(novaDataInicio, novaDataFim);

    try {
        validarHorarios(tarefa, diasReposicionamento);

        tarefa.inicio = novaDataInicio.format('YYYY-MM-DD HH:mm:ss');
        tarefa.fim = novaDataFim.format('YYYY-MM-DD HH:mm:ss');

        await api.post(`/tarefa/${tarefa.id}`, {
            inicio: novaDataInicio.format('YYYY-MM-DD'),
            fim: novaDataFim.format('YYYY-MM-DD'),
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

const validarHorarios = (tarefa, diasReposicionamento) => {
    const horariosDisponiveisDaMaquina = horariosDisponiveis.value.filter((horario) => {
        return horario.id_maquina == tarefa.id_maquina;
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

        <Button title="Adicionar Tarefa" icon="pi pi-plus" rounded @click="adicionarTarefa()" />
    </header>

    <div class="gantt-chart">
        <div class="gantt-header" :style="`grid-template-columns: 200px repeat(${qtdDiasExibidos + 1}, 100px)`">
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

