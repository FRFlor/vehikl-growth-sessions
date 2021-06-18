<template>
    <form @submit.prevent class="create-growth-session edit-growth-session-form bg-white w-full p-4 text-left">
        <div class="mb-4 flex justify-between">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="date">
                    Date
                </label>
                <div :class="{'error-outline': getError('date')}"
                     class="border p-1 border-gray-400 flex justify-center">
                    <date-picker id="date" tabIndex="1" v-model="date"/>
                </div>
            </div>

            <button
                :class="{'opacity-25 cursor-not-allowed': !isReadyToSubmit}"
                :disabled="! isReadyToSubmit"
                @click="onSubmit"
                class="mt-6 w-48 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                tabindex="6"
                type="submit"
                ref="submit-button"
                v-text="isCreating? 'Create' : 'Update'">
            </button>
        </div>

        <div class="mb-4 flex">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">
                    Start
                </label>
                <vue-timepicker :class="{'error-outline': getError('start_time')}"
                                :minute-interval="15"
                                advanced-keyboard
                                auto-scroll
                                format="hh:mm a"
                                hide-disabled-items
                                id="start_time"
                                tabindex="2"
                                v-model="startTime"/>
            </div>
            <div class="ml-12">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">
                    End
                </label>
                <vue-timepicker :class="{'error-outline': getError('end_time')}"
                                :minute-interval="15"
                                advanced-keyboard
                                auto-scroll
                                format="hh:mm a"
                                hide-disabled-items
                                id="end_time"
                                tabindex="3"
                                v-model="endTime"/>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                Title
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   :class="{'error-outline': getError('title')}"
                   id="title"
                   maxlength="45"
                   placeholder="In a short sentence, what is this growth session about?"
                   tabindex="4"
                   type="text"
                   v-model="title"/>
        </div>

        <div class="mb-4 mt-6 flex items-center justify-between">
            <label class="block text-gray-700 text-sm font-bold mb-2" v-if="isCreating">
                <input ref="is-public" v-model="isPublic" type="checkbox"> Is Public
            </label>

            <label class="block text-gray-700 text-sm font-bold mb-2">
                <input ref="no-limit" v-model="isLimitless" type="checkbox"> No Limit
            </label>

            <div v-if="!isLimitless" class="flex items-center">
                <label class="block text-gray-700 text-sm font-bold mr-4" for="limit">
                    Limit
                </label>
                <input
                    id="limit"
                    ref="attendee-limit"
                    v-model.number="attendeeLimit"
                    :class="{'error-outline': getError('limit')}"
                    class="w-24 text-center shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    min="4"
                    placeholder="Limit of participants"
                    tabindex="4"
                    type="number"/>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="topic">
                Topic
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      :class="{'error-outline': getError('topic')}"
                      id="topic"
                      placeholder="Do you want to provide more details about this growth session?"
                      rows="4"
                      tabindex="5"
                      v-model="topic"/>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                <input ref="auto-generate-zoom-meeting" v-model="createZoomMeeting" type="checkbox">
                Auto-generate a Zoom meeting for this growth session
            </label>
        </div>

        <div class="mb-4" v-if="(discordChannels.length > 0)">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="discord_channel">
                Discord Channel
            </label>
            <v-select id="discord_channel"
                      name="discord_channel"
                      :options="discordChannels"
                      v-model="discordChannel"></v-select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                Location
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      :class="{'error-outline': getError('location')}"
                      id="location"
                      placeholder="Where should people go to participate?"
                      rows="2"
                      tabindex="6"
                      v-model="location"/>
        </div>
    </form>
</template>

<script lang="ts">
import {Component, Prop, Vue, Watch} from 'vue-property-decorator';
import {IGrowthSession, IStoreGrowthSessionRequest, IUser, IValidationError} from '../types';
import VueTimepicker from 'vue2-timepicker'
import {GrowthSessionApi} from '../services/GrowthSessionApi';
import {DateTime} from '../classes/DateTime';
import DatePicker from './DatePicker.vue';
import {DiscordChannelApi} from "../services/DiscordChannelApi";
import {IDropdownOption} from "../types/IDropdownOption";

@Component({components: {DatePicker, VueTimepicker}})
    export default class GrowthSessionForm extends Vue {
        @Prop({required: true}) owner!: IUser;
        @Prop({required: false, default: null}) growthSession!: IGrowthSession;
        @Prop({required: false, default: ''}) startDate!: string;

        startTime: string = '03:30 pm';
        endTime: string = '05:00 pm';
        location: string = '';
        title: string = '';
        attendeeLimit: number = 4;
        topic: string = '';
        date: string = '';
        isPublic: boolean = false;
        validationErrors: IValidationError | null = null;
        isLimitless: boolean = false;
        discordChannel: IDropdownOption = {value: '', label: ''};
        discordChannels: Array<Object> = [];
        createZoomMeeting: boolean = false;

        mounted() {
            this.date = this.startDate;
            let input = document.getElementById('title');
            if (input) {
                input.focus();
            }

            this.getDiscordChannels();

            if (this.growthSession) {
                this.date = this.growthSession.date;
                this.startTime = DateTime.parseByTime(this.growthSession.start_time).toTimeString12Hours();
                this.endTime = DateTime.parseByTime(this.growthSession.end_time).toTimeString12Hours();
                this.location = this.growthSession.location;
                this.title = this.growthSession.title;
                this.topic = this.growthSession.topic;
                this.isLimitless = ! this.growthSession.attendee_limit;
                this.attendeeLimit = this.growthSession.attendee_limit || 4;
            }
        }

        onSubmit() {
            if (this.isCreating) {
                return this.createGrowthSession();
            }
            this.updateGrowthSession();
        }

        onRequestFailed(exception: any) {
            if (exception.response?.status === 422) {
                this.validationErrors = exception.response.data;
            } else {
                alert('Something went wrong :(');
            }
        }

        getError(field: string): string {
            let errors = this.validationErrors?.errors[field];
            return errors ? errors[0] : '';
        }

        async createGrowthSession() {
            try {
                let growthSession: IGrowthSession = await GrowthSessionApi.store(this.storeOrUpdatePayload);
                this.$emit('submitted', growthSession);
            } catch (e) {
                this.onRequestFailed(e);
            }
        }

        async updateGrowthSession() {
            try {
                let growthSession: IGrowthSession = await GrowthSessionApi.update(this.growthSession, this.storeOrUpdatePayload);
                this.$emit('submitted', growthSession);
            } catch (e) {
                this.onRequestFailed(e);
            }
        }

        async getDiscordChannels() {
            try {
                const discordChannels = await DiscordChannelApi.index();
                this.discordChannels = discordChannels.map(discordChannel => {
                    return {
                        label: discordChannel.name,
                        value: discordChannel.id
                    };
                });
            } catch (e) {
                this.onRequestFailed(e);
            }
        }

        get isCreating(): boolean {
            return !this.growthSession;
        }

        get isReadyToSubmit(): boolean {
            return !!this.startTime && !!this.endTime && !!this.date && !!this.location && !!this.topic && !!this.title;
        }

        get storeOrUpdatePayload(): IStoreGrowthSessionRequest {
            return {
                location: this.location,
                topic: this.topic,
                title: this.title,
                date: this.date,
                start_time: this.startTime,
                end_time: this.endTime,
                is_public: this.isPublic,
                attendee_limit: this.isLimitless ? undefined : this.attendeeLimit,
                discord_channel_id: this.discordChannel.value || undefined,
                create_zoom_meeting: this.createZoomMeeting,
            }
        }

        @Watch('discordChannel')
        onDiscordChannelChanged(value: IDropdownOption) {
            if(!this.location || this.location.startsWith('Discord Channel: ')) {
                this.location = `Discord Channel: ${value.label}`
            }
        }
    }
</script>

<style lang="scss" scoped>
    .error-outline {
        outline: red solid 2px;
    }
</style>
