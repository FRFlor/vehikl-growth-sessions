<template>
    <div class="max-w-5xl text-blue-600">
        <div class="mb-8 flex flex-col lg:flex-row lg:justify-between items-center">
            <modal :dynamic="true" :height="600" :width="500" name="growth-session-form">
                <div class="flex w-full h-full overflow-y-scroll">
                    <growth-session-form :growth-session="growthSession"
                                         :owner="growthSession.owner"
                                         :start-date="growthSession.date"
                                         class="growth-session-form"
                                         @submitted="onGrowthSessionUpdated"/>
                </div>
            </modal>
            <h2 class="text-2xl lg:text-3xl font-sans font-light flex items-center text-blue-700">
                <a :href="growthSession.owner.githubURL" ref="owner-avatar-link">
                    <v-avatar class="mr-4" :src="growthSession.owner.avatar"
                              :alt="`${growthSession.owner.name}'s Avatar`"/>
                </a>
                {{ growthSession.title }}
            </h2>
            <div>
                <button
                    class="join-button w-32 bg-blue-500 hover:bg-blue-700 focus:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="growthSession.join()"
                    v-show="growthSession.canJoin(userJson)">
                    Join
                </button>
                <button
                    class="leave-button w-32 bg-red-500 hover:bg-red-700 focus:bg-red-700  text-white font-bold py-2 px-4 rounded"
                    @click.stop="growthSession.leave()"
                    v-show="growthSession.canLeave(userJson)">
                    Leave
                </button>
                <button
                    class="update-button w-32 bg-orange-500 hover:bg-orange-700 focus:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="$modal.show('growth-session-form')"
                    v-if="growthSession.canEditOrDelete(userJson)">
                    Edit
                </button>
                <button
                    class="delete-button w-16 bg-red-500 hover:bg-red-700 focus:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    @click.stop="deleteGrowthSession"
                    v-if="growthSession.canEditOrDelete(userJson)">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="flex flex-col lg:flex-row flex-wrap">
            <div class="flex-1 mr-2 max-w-5xl">
                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Topic</h3>
                <pre class="description font-sans m-5 break-words-fixed whitespace-pre-wrap"
                     v-text="growthSession.topic"/>
                <comment-list class="mt-24 max-w-xl" :growth-session="growthSession" :user="userJson"/>
            </div>
            <div class="flex-none max-w-md">
                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Host:</h3>
                    <span>{{ growthSession.owner.name }}</span>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Location:</h3>
                    <location-renderer :location-string="growthSession.location"/>
                    <a v-if="growthSession.discord_channel_id"
                       class="location-icon"
                       target="_blank"
                       :href="this.discordChannelUrl">
                        <svg enable-background="new 0 0 24 24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <g fill="#5c6bc0">
                                <path
                                    d="m3.58 21.196h14.259l-.681-2.205c.101.088 5.842 5.009 5.842 5.009v-21.525c-.068-1.338-1.22-2.475-2.648-2.475l-16.767.003c-1.427 0-2.585 1.139-2.585 2.477v16.24c0 1.411 1.156 2.476 2.58 2.476zm10.548-15.513-.033.012.012-.012zm-7.631 1.269c1.833-1.334 3.532-1.27 3.532-1.27l.137.135c-2.243.535-3.26 1.537-3.26 1.537.104-.022 4.633-2.635 10.121.066 0 0-1.019-.937-3.124-1.537l.186-.183c.291.001 1.831.055 3.479 1.26 0 0 1.844 3.15 1.844 7.02-.061-.074-1.144 1.666-3.931 1.726 0 0-.472-.534-.808-1 1.63-.468 2.24-1.404 2.24-1.404-3.173 1.998-5.954 1.686-9.281.336-.031 0-.045-.014-.061-.03v-.006c-.016-.015-.03-.03-.061-.03h-.06c-.204-.134-.34-.2-.34-.2s.609.936 2.174 1.404c-.411.469-.818 1.002-.818 1.002-2.786-.066-3.802-1.806-3.802-1.806 0-3.876 1.833-7.02 1.833-7.02z"/>
                                <path
                                    d="m14.308 12.771c.711 0 1.29-.6 1.29-1.34 0-.735-.576-1.335-1.29-1.335v.003c-.708 0-1.288.598-1.29 1.338 0 .734.579 1.334 1.29 1.334z"/>
                                <path
                                    d="m9.69 12.771c.711 0 1.29-.6 1.29-1.34 0-.735-.575-1.335-1.286-1.335l-.004.003c-.711 0-1.29.598-1.29 1.338 0 .734.579 1.334 1.29 1.334z"/>
                            </g>
                        </svg>
                    </a>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">MobTime:</h3>
                    <a :href="mobtimeUrl" target="_blank">{{ mobtimeUrl }}</a>
                </div>

                <div class="mb-3">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Time:</h3>
                    <span class="mr-2">{{ date }}</span>
                    <span class="whitespace-no-wrap">( {{ time }} )</span>
                </div>

                <div class="mb-3" v-if="growthSession.attendee_limit">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Attendee Limit:</h3>
                    <span class="attendee_limit">{{ growthSession.attendee_limit }}</span>
                </div>

                <div class="mb-3" v-if="growthSession.zoom_meeting_id">
                    <h3 class="text-2xl font-sans inline font-light mr-3 text-blue-700">Zoom Link:</h3>
                    <a :href="`https://us02web.zoom.us/j/${growthSession.zoom_meeting_id}`" target="_blank">Zoom Meeting Link</a>
                </div>

                <h3 class="text-2xl font-sans font-light mb-3 text-blue-700">Attendees</h3>
                <ul>
                    <li v-for="attendee in growthSession.attendees">
                        <a ref="attendee" :href="attendee.githubURL" class="flex items-center ml-6 my-4">
                            <v-avatar size="12" class="mr-3" :src="attendee.avatar" :alt="`${attendee.name}'s Avatar`"/>
                            {{ attendee.name }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import {Component, Prop, Vue} from 'vue-property-decorator';
import {IGrowthSession, IUser} from '../types';
import {DateTime} from '../classes/DateTime';
import {GrowthSession} from '../classes/GrowthSession';
import VueTimepicker from 'vue2-timepicker';
import Datepicker from 'vuejs-datepicker';
import CommentList from './CommentList.vue';
import VAvatar from './VAvatar.vue';
import LocationRenderer from './LocationRenderer.vue';
import GrowthSessionForm from "./GrowthSessionForm.vue";

@Component({components: {LocationRenderer, VAvatar, CommentList, VueTimepicker, Datepicker, GrowthSessionForm}})
export default class GrowthSessionView extends Vue {
    @Prop({required: false}) userJson!: IUser;
    @Prop({required: true}) growthSessionJson!: IGrowthSession;
    @Prop({required: false}) discordGuildId!: string;
    growthSession: GrowthSession = new GrowthSession(this.growthSessionJson);

    get date(): string {
        return `${DateTime.parseByDate(this.growthSession.date).format('MMM-DD')}`
    }

    get time(): string {
        return `${this.growthSession.startTime} - ${this.growthSession.endTime}`;
    }

    get mobtimeUrl(): string {
        return `https://mobti.me/vgs-${this.growthSessionJson.id}`;
    }

    get discordChannelUrl(): string {
        return `discord://discordapp.com/channels/${this.discordGuildId}/${this.growthSession.discord_channel_id}`;
    }

    async deleteGrowthSession() {
        if (confirm('Are you sure you want to delete?')) {
            await this.growthSession.delete();
            window.location.assign('/');
        }
    }

    async onGrowthSessionUpdated(growthSession: GrowthSession) {
        this.growthSession.refresh(growthSession)
        this.$modal.hide('growth-session-form')
    }
}
</script>

<style lang="scss" scoped>
.description {
    min-height: 10rem;
}

.location-icon svg {
    width: 24px;
    display: inline-block;
    margin-left: 4px;
}
</style>
