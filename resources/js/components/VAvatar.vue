<template>
    <div :class="`w-${size} h-${size} relative`">
        <div class="w-full h-full overflow-hidden rounded-full shadow-inner group" :class="containerClass">
            <img :src="src" :alt="alt"
                 class="visible object-cover object-center w-full h-full group-hover:hidden"/>
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';

    @Component
    export default class VAvatar extends Vue {
        @Prop({default: 20}) readonly size!: string;
        @Prop({required: true}) readonly src!: string;
        @Prop({required: true}) readonly alt!: string;
        @Prop({required: false, default: ''}) readonly containerClass!: string;
    }
</script>
