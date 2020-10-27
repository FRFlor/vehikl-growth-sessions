import {mount, Wrapper} from '@vue/test-utils';
import MobForm from './MobForm.vue';
import {IStoreSocialMobRequest, IUser} from '../types';
import flushPromises from 'flush-promises';
import {SocialMobApi} from '../services/SocialMobApi';

const user: IUser = {
    avatar: 'lastAirBender.jpg',
    email: 'jack@bauer.com',
    github_nickname: 'jackjack',
    id: 987,
    name: 'Jack Bauer'
};
const startDate: string = "2020-06-25";

describe('MobForm', () => {
    let wrapper: Wrapper<MobForm>;

    beforeEach(() => {
        wrapper = mount(MobForm, {propsData: {owner: user, startDate}});
        SocialMobApi.store = jest.fn().mockImplementation(mob => mob);
        SocialMobApi.update = jest.fn().mockImplementation(mob => mob);
    });

    const baseMobRequest: IStoreSocialMobRequest = {
        location: 'The mob location',
        topic: 'Chosen topic',
        title: 'Chosen title',
        date: '2020-10-01',
        start_time: '4:45 pm',
    }

    describe('allows a mob to be created', () => {
        type TMobCreationScenario = [string, IStoreSocialMobRequest]

        const scenarios: TMobCreationScenario[] = [
            [
                'Can accept no limit and no end time',
                {...baseMobRequest, attendee_limit: undefined, end_time: undefined}
            ],
            [
                'Can accept a limit',
                {
                    ...baseMobRequest,
                    attendee_limit: 4
                }
            ],
            [
                'Can accept an end time',
                {
                    ...baseMobRequest,
                    end_time: '5:45 pm',
                }
            ]
        ]

        it.each(scenarios)('%s', async (testTitle, payload) => {
            const {
                title: chosenTitle,
                topic: chosenTopic,
                location: chosenLocation,
                start_time: chosenStartTime,
                attendee_limit: chosenLimit,
            } = payload;

            window.confirm = jest.fn();
            wrapper.vm.$data.startTime = chosenStartTime;
            wrapper.find('#title').setValue(chosenTitle);
            wrapper.find('#topic').setValue(chosenTopic);
            wrapper.find('#location').setValue(chosenLocation);

            if (! chosenLimit) {
                wrapper.findComponent({ref: 'no-limit'}).trigger('click');
            }

            if (chosenLimit) {
                wrapper.findComponent({ref: 'attendee-limit'}).setValue(chosenLimit);
            }
            await wrapper.vm.$nextTick();
            wrapper.find('button[type="submit"]').trigger('click');
            await flushPromises();

            const expectedPayload: IStoreSocialMobRequest = {
                title: chosenTitle,
                attendee_limit: chosenLimit,
                location: chosenLocation,
                date: startDate,
                start_time: chosenStartTime,
                end_time: '05:00 pm',
                topic: chosenTopic
            };

            if (!chosenLimit) {
                delete expectedPayload.attendee_limit;
            }

            expect(SocialMobApi.store).toHaveBeenCalledWith(expectedPayload);
        });
    });

    it('emits submitted event on success', async () => {
        wrapper.vm.$data.startTime = '3:30 pm';
        wrapper.find('#title').setValue('Something');
        wrapper.find('#topic').setValue('Anything');
        wrapper.find('#location').setValue('Anywhere');
        await wrapper.vm.$nextTick();
        wrapper.find('button[type="submit"]').trigger('click');
        await flushPromises();
        expect(wrapper.emitted('submitted')).toBeTruthy();
    });

    it('starts with the submit button disabled', () => {
        expect(wrapper.find('button[type="submit"]').element).toBeDisabled();
    });

    it('enables the submit button when all required fields are filled', async () => {
        wrapper.vm.$data.startTime = '3:30 pm';
        wrapper.find('#title').setValue('Something');
        wrapper.find('#topic').setValue('Anything');
        wrapper.find('#location').setValue('Anywhere');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('button[type="submit"]').element).not.toBeDisabled();
    });

    it('has a no limit checkbox', () => {
        expect(wrapper.findComponent({ref: 'no-limit'}).exists()).toBeTruthy();
    })

    it('hides the attendee limit input, if no limit checkbox is checked', async () => {
        wrapper.findComponent({ref: 'no-limit'}).trigger('click')
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent({ref: 'attendee-limit'}).exists()).toBeFalsy();
    })
});
