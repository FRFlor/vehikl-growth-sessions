<template>
  <div class="container mx-auto text-blue-600">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
      <ActivityFriends :peers="peers"></ActivityFriends>
      <div></div>
      <div class="md:col-span-2">
        <ActivityGrid title="Joined Sessions" :mobs="joined_mobs"></ActivityGrid>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {IUser, ISocialMob, IMobConnection} from '../types';
    import VAvatar from './VAvatar.vue';
    import ActivityFriends from './ActivityFriends.vue';
    import ActivityGrid from './ActivityGrid.vue';

    @Component({components: {VAvatar,ActivityFriends,ActivityGrid}})
    export default class ActivityView extends Vue {
        @Prop({required: true}) user!: IUser;
        @Prop({required: true}) hosted_mobs!: ISocialMob[];
        @Prop({required: true}) joined_mobs!: ISocialMob[];
        @Prop({required: true}) peers!: IMobConnection[];
    }
</script>

<style lang="scss" scoped>
</style>
