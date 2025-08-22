<script setup>
import { ref, computed } from 'vue';
import { useTarefasStore } from "@/stores/tarefas.js";
import { useLoadingStore } from "@/stores/loading.js";
import { useToast } from "primevue/usetoast";
import { getDates } from "@/helpers/getDates.js";
import { getDiaSemana } from "@/helpers/getDiaSemana.js";
import { HorarioIndisponivelError } from "@/errors/HorarioIndisponivelError.js";
import api from "@/axios.js";
import dayjs from "dayjs";
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import Select from 'primevue/select';
import Button from "primevue/button";
import Divider from 'primevue/divider';
import AdicionarTarefa from "./AdicionarTarefa.vue";
import LinhaGantt from './LinhaGantt.vue';

dayjs.extend(isSameOrBefore);

const toast = useToast();

const props = defineProps(['maquinas' , 'horariosDisponiveis']);

const tarefasStore = useTarefasStore();

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

const horasExibidas = ['00h', '06h', '12h', '18h'];

const larguraDiaPx = 200;
const alturaTarefaPx = 60;

const loading = useLoadingStore();

const dias = computed(() => {
    return getDates(dayjs().startOf('day'), dayjs().add(qtdDiasExibidos.value.dias, 'day'));
});

const reposicionarTarefa = async ({ tarefa, deslocamentoX, deslocamentoY }) => {
    let idMaquina = tarefa.id_maquina;

    if (deslocamentoY !== 0) {
        const maquina = props.maquinas.find((maquina) => {
            return maquina.id === tarefa.id_maquina + deslocamentoY;
        });

        if (maquina) {
            idMaquina = maquina.id;
        }
    }

    const novaDataInicio = dayjs(tarefa.inicio).add(deslocamentoX, 'minute');
    const novaDataFim = dayjs(tarefa.fim).add(deslocamentoX, 'minute');

    const diasReposicionamento = getDates(novaDataInicio, novaDataFim);

    try {
        //validarHorarios(tarefa, diasReposicionamento, idMaquina);

        loading.show('Reposicionando Tarefa...');

        await api.post(`/tarefa/${tarefa.id}/reposicionar`, {
            inicio: novaDataInicio.format('YYYY-MM-DD HH:mm:ss'),
            fim: novaDataFim.format('YYYY-MM-DD HH:mm:ss'),
            id_maquina: idMaquina
        });

        loading.hide();

        tarefasStore.getTarefas();
    } catch (e) {
        loading.hide();

        if (e instanceof HorarioIndisponivelError) {
            toast.add({ severity: 'error', summary: e.title, detail: e.message, life: 3000 });
        } else {
            toast.add({ severity: 'error', summary: 'Erro', detail: e.message, life: 3000 });
        }
    }
}

const adicionarTarefa = () => {
    adicionarTarefaDialog.value.openDialog();
}

const validarHorarios = (tarefa, diasReposicionamento, idMaquina) => {
    // const horariosDisponiveisDaMaquina = props.horariosDisponiveis.filter((horario) => {
    //     return horario.id_maquina == idMaquina;
    // });
    //
    // diasReposicionamento.forEach((dia) => {
    //     const diaSemana = getDiaSemana(dia.day()).name;
    //
    //     const disponivel = horariosDisponiveisDaMaquina.some((horario) => {
    //         return diaSemana === horario.dia_semana &&
    //                tarefa.periodo_diario_inicio >= horario.hora_inicio &&
    //                tarefa.periodo_diario_fim <= horario.hora_fim;
    //     });
    //
    //     if (!disponivel) {
    //         throw new HorarioIndisponivelError('Não foi possível mover tarefa', 'Horário indisponível');
    //     }
    // });
}

const getLeftByIndex = (index) => {
    const total = horasExibidas.length;

    return (index / total) * 100;
};
</script>

<template>
    <AdicionarTarefa ref="adicionarTarefaDialog" @recarregar-tarefas="tarefasStore.getTarefas()" />

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
        <div
            class="gantt-header"
            :style="`grid-template-columns: 200px repeat(${qtdDiasExibidos.dias + 1}, ${larguraDiaPx}px)`"
        >
            <div class="task-label-header">
                Tarefa
            </div>

            <div v-for="dia in dias" :key="dia" class="day-header">
                <span>{{ dia.format('DD/MM') }}</span>

                <span style="font-weight: normal; margin-bottom: 5px;">
                    {{ getDiaSemana(dia.day()).label }}
                </span>

                <Divider style="margin: 0;" />

                <div class="hours-container">
                    <div
                        v-for="(hora, index) in horasExibidas"
                        :key="hora"
                        class="hour"
                        :style="{ left: getLeftByIndex(index) + '%' }"
                    >
                        <Divider style="margin: 0;" layout="vertical" />

                        <span style="margin: 3px 0;">{{ hora }}</span>
                    </div>
                </div>
            </div>
        </div>

        <LinhaGantt
            v-for="tarefa in tarefasStore.data"
            :key="tarefa.id"
            :tarefa="tarefa"
            :dias="dias"
            :largura-dia-px="larguraDiaPx"
            :altura-tarefa-px="alturaTarefaPx"
            :horas-exibidas="horasExibidas"
            :horarios-disponiveis="props.horariosDisponiveis"
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
    padding: 7px 0 0 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    border-left: 1px solid #27272a;
}

.hours-container {
    position: relative;
    width: 100%;
    height: 26px;
    display: flex;
    justify-content: space-evenly;
}

.hour {
    position: absolute;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}
</style>

