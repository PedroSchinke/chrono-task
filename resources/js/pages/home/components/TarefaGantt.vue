<script setup>
import { ref, computed } from 'vue';
import { useToast } from "primevue/usetoast";
import dayjs from "dayjs";
import Popover from 'primevue/popover';
import ColorPicker from 'primevue/colorpicker';
import Toast from "primevue/toast";
import api from "@/axios.js";

const toast = useToast();

const props = defineProps(['tarefa', 'dias', 'maquina']);
const emit = defineEmits(['reposicionar']);

const cor = computed(() => {
    return props.tarefa.cor.startsWith('#') ? props.tarefa.cor : `#${props.tarefa.cor}`;
});

const tarefaPopover = ref();
const blocoFoiArrastado = ref(false);

const dragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);
const offsetX = ref(0);
const offsetY = ref(0);

const diasEntre = (inicio, fim) => {
    return dayjs(fim).startOf('day').diff(dayjs(inicio).startOf('day'), 'day');
}

const  blocoStyle = computed(() => {
    const inicio = diasEntre(props.dias[0], props.tarefa.inicio);
    const duracao = diasEntre(props.tarefa.inicio, props.tarefa.fim);

    let left = dragging.value ? inicio * 100 + offsetX.value : inicio * 100;
    let top = dragging.value ? offsetY.value : 0;

    left = left < 0 ? 0 : left;

    return {
        left: `${left}px`,
        top: `${top}px`,
        width: `${(duracao + 1) * 100}px`,
        backgroundColor: cor.value,
        cursor: 'grab',
        position: 'absolute',
    }
});

const startDrag = (event) => {
    dragging.value = true;
    blocoFoiArrastado.value = false;
    dragStartX.value = event.clientX;
    dragStartY.value = event.clientY;

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
}

const onDrag = (event) => {
    const deslocamento = Math.abs(event.clientX - dragStartX.value);

    if (deslocamento > 5) {
        blocoFoiArrastado.value = true;
    }

    offsetX.value = event.clientX - dragStartX.value;
    offsetY.value = event.clientY - dragStartY.value;
}

const stopDrag = () => {
    dragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);

    const deslocamento = offsetX.value / 100;

    const deslocamentoInteiros = deslocamento > 0
                                 ? Math.floor(deslocamento)
                                 : Math.ceil(deslocamento);

    const deslocamentoY = offsetY.value;
    let deslocamentoMaquina = Math.trunc(offsetY.value / 60);

    if (deslocamentoInteiros !== 0 || deslocamento % 1 > 0.8 || deslocamento % 1 < -0.8 || deslocamentoMaquina !== 0) {
        let deslocamentoDias = deslocamentoInteiros;

        if (deslocamento % 1 > 0.8) {
            deslocamentoDias++;
        }

        if (deslocamento % 1 < -0.8) {
            deslocamentoDias--;
        }

        emit('reposicionar', { tarefa: props.tarefa, deslocamento: deslocamentoDias, posY: deslocamentoY });
    }

    offsetX.value = 0;
}

const alterarCor = async (event) => {
    const params = { cor: '#' + event.value };

    try {
        const resp = await api.post(`/tarefa/${props.tarefa.id}/cor`, params);
    } catch (e) {
        cor.value = props.tarefa.cor;

        toast.add({ severity: 'error', summary: 'Erro', detail: 'Não foi possível alterar a cor', life: 3000 });
    }
}

const toggle = (event) => {
    if (!blocoFoiArrastado.value) {
        tarefaPopover.value.toggle(event);
    }
}
</script>

<template>
    <Toast />

    <div
        :id="`tarefa-${tarefa.id}`"
        :style="blocoStyle"
        class="tarefa-bloco"
        @mousedown="startDrag"
        @click="toggle"
    >
        <div class="bloco-texto">
            {{ props.tarefa.titulo }}
        </div>
    </div>

    <Popover ref="tarefaPopover">
        <div class="flex flex-col gap-4 w-[25rem]">
            <div class="popover-header">
                <h2 style="margin-top: 5px;">{{ props.tarefa.titulo }}</h2>

                <ColorPicker v-model="cor" style="margin-bottom: 15px;" @it change="alterarCor" />
            </div>

            <div class="popover-tarefa-infos">
                <div class="popover-info">
                    <span style="font-weight: bold;">Descrição</span>

                    <span>{{ props.tarefa.descricao }}</span>
                </div>

                <div class="popover-info">
                    <span style="font-weight: bold;">Máquina</span>

                    <span>{{ props.maquina.nome }}</span>
                </div>

                <div class="popover-info">
                    <span style="font-weight: bold;">Período</span>

                    <span>
                        {{ dayjs(props.tarefa.inicio).format('DD/MM/YYYY') }} - {{ dayjs(props.tarefa.fim).format('DD/MM/YYYY') }}
                    </span>
                </div>

                <div class="popover-info">
                    <span style="font-weight: bold;">Período de atividade diário</span>

                    <span>
                        {{ props.tarefa.periodo_diario_inicio.slice(0, 5) }} - {{ props.tarefa.periodo_diario_fim.slice(0, 5) }}
                    </span>
                </div>
            </div>
        </div>
    </Popover>
</template>

<style scoped>
.tarefa-bloco {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border-radius: 4px;
    user-select: none;
    z-index: 10;
}

.bloco-texto {
    white-space: break-spaces;
    text-align: center;
}

.popover-header {
    display: flex;
    align-items: center;
    gap: 10px;
}

.popover-tarefa-infos {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.popover-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
</style>
