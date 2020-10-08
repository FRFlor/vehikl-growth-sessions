<template>
  <div class="overflow-hidden rounded shadow-sm hover:shadow-md transition-shadow duration-300 mx-w-sm">
    <div class="px-4 pt-4 pb-2">
      <div class="mb-2 text-xl font-bold">
        Friends You've Grown With
      </div>
      <table class="w-full table-auto">
        <thead>
          <tr>
            <th class="p-1 text-left">Peer</th>
            <th class="p-1 text-right">How Often</th>
          </tr>
        </thead>
        <tbody>

          <tr v-for="peer in peers" :key="peer.user.id">
            <td class="py-2 border-b border-b-gray-200">
              <div class="flex flex-row items-center justify-start">
                <v-avatar :src="peer.user.avatar" alt="" size="8"></v-avatar>
                <div class="ml-4">
                  {{ peer.user.name }}
                </div>
              </div>
            </td>
            <td class="py-2 text-right border-b border-b-gray-200">
              {{ (peer.count / totalMobs * 100).toFixed(0) }}%
            </td>
          </tr>
        </tbody>
      </table>

      <footer class="py-1 mt-1 text-sm text-gray-500">
        Based on the {{ totalMobs }} growth session{{ totalMobs === 1 ? '' : 's' }} you've participated in.
      </footer>
    </div>
  </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {IMobConnection} from '../types';
    import VAvatar from './VAvatar.vue';

    @Component({components: {VAvatar}})
    export default class ActivityFriends extends Vue {
        @Prop({required: true}) peers!: IMobConnection[];

        get totalMobs(): number {
            return this.peers.reduce((num: number, peer : IMobConnection) => (
                num + peer.count
            ), 0);
        }
    }
</script>
