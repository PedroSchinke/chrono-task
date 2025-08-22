<script setup>
import { useLoadingStore } from "@/stores/loading.js";
import ProgressSpinner from "primevue/progressspinner";

const loading = useLoadingStore();
</script>

<template>
    <transition>
        <div v-if="loading.ativo" class="modal-mask">
            <div class="modal-container">
                <ProgressSpinner stroke-width="3" />

                <h2 style="color: white; white-space: nowrap">
                    <slot>{{ loading.message }}</slot>
                </h2>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.5);
    transition: opacity 0.3s ease;
}

.modal-container {
    width: 100px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: all 0.3s ease;
}
</style>
