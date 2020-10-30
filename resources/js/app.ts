import "./bootstrap";
import Vue from 'vue';
import WeekView from "./components/WeekView.vue";
import MobView from "./components/MobView.vue";
import ActivityView from "./components/ActivityView.vue";
import MobEdit from "./components/MobEdit.vue";
import VAvatar from "./components/VAvatar.vue";
import VModal from 'vue-js-modal';

Vue.use(VModal);

new Vue({
    el: '#app',
    components: {WeekView, MobView, ActivityView, MobEdit, VAvatar}
});
