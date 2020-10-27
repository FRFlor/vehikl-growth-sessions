import {mount, Wrapper} from '@vue/test-utils';
import MobCard from './MobCard.vue';
import {IUser} from '../types';
import {SocialMobApi} from '../services/SocialMobApi';
import {DateTime} from '../classes/DateTime';
import {SocialMob} from '../classes/SocialMob';

const ownerOfTheMob: IUser = {
    name: 'Jack Bauer',
    github_nickname: 'jackjack',
    id: 1,
    email: 'jack@bauer.com',
    avatar: 'theLastAirBender.jpg'
};

const attendee: IUser = {
    name: 'Alice',
    github_nickname: 'alisss',
    id: 2,
    email: 'alice@ecila.com',
    avatar: 'avatar.jpg'
};

const outsider: IUser = {
    name: 'Rudolf',
    github_nickname: 'deer123',
    id: 3,
    email: 'red@nose.com',
    avatar: 'avatar.jpg'
};

const mobData: SocialMob = new SocialMob({
    id: 0,
    owner: ownerOfTheMob,
    location: 'Somewhere over the rainbow',
    date: '2020-05-08',
    start_time: '03:30 pm',
    end_time: '05:00 pm',
    title: 'Foobar',
    topic: 'The fundamentals of Foobar',
    attendee_limit: 41,
    attendees: [
        attendee
    ],
    comments: []
});

describe('MobCard', () => {
    let wrapper: Wrapper<MobCard>;
    beforeEach(() => {
        window.confirm = jest.fn();
        DateTime.setTestNow('2020-05-01 00:00:00.0000');
        wrapper = mount(MobCard, {propsData: {socialMob: mobData}})
    });

    it('displays the owner name', () => {
        expect(wrapper.text()).toContain(mobData.owner.name);
    });

    it('displays the mob topic', () => {
        expect(wrapper.text()).toContain(mobData.topic);
    });

    it('displays the mob location', () => {
        expect(wrapper.text()).toContain(mobData.location);
    });

    it('displays the number of attendees', () => {
        expect(wrapper.find('.attendees-count').text()).toContain(mobData.attendees.length);
    });

    it('displays the attendee limit', () => {
        expect(wrapper.find('.attendee-limit').text()).toContain(mobData.attendee_limit);
    });

    it('does not display the attendee limit if there is none', () => {
        const noLimitMob = new SocialMob({...mobData, attendee_limit: null});
        wrapper = mount(MobCard, {propsData: {socialMob: noLimitMob}})
        // TODO: is there a better way to make sure something isn't displayed in the card??
        expect(wrapper.find('.attendee-limit').element).not.toBeDefined();
    })

    it('does not display the join button to the owner of the mob', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: ownerOfTheMob}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does not display the join button if you are already part of the mob', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: attendee}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does not display the join button to guests', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData}});
        expect(wrapper.find('.join-button').element).not.toBeVisible();
    });

    it('does display the join button if you are authenticated and not part of the mob', () => {
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: outsider}});
        expect(wrapper.find('.join-button').element).toBeVisible();
    });

    it('allows a user to join a growth session', () => {
        window.open = jest.fn();
        SocialMobApi.join = jest.fn().mockImplementation(mob => mob);
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: outsider}});
        wrapper.find('.join-button').trigger('click');
        expect(SocialMobApi.join).toHaveBeenCalledWith(mobData);
    });

    it('allows a user to leave a growth session', () => {
        SocialMobApi.leave = jest.fn().mockImplementation(mob => mob);
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: attendee}});
        wrapper.find('.leave-button').trigger('click');
        expect(SocialMobApi.leave).toHaveBeenCalledWith(mobData);
    });

    it('prompts for confirmation when the owner clicks on the delete button', ()=> {
        window.confirm = jest.fn();
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: ownerOfTheMob}});
        wrapper.find('.delete-button').trigger('click');
        expect(window.confirm).toHaveBeenCalled();
    });

    it('deletes the mob if the user clicks on the delete button and confirms', ()=> {
        SocialMobApi.delete = jest.fn();
        window.confirm = jest.fn().mockReturnValue(true);
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: ownerOfTheMob}});
        wrapper.find('.delete-button').trigger('click');
        expect(SocialMobApi.delete).toHaveBeenCalledWith(mobData);
    });

    it('does not display the edit button to the owner if the date of the mob is in the past', () => {
        const oneDayAfterTheMob = DateTime.parseByDate(mobData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheMob);
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: ownerOfTheMob}});

        expect(wrapper.find('.delete-button').element).not.toBeVisible()
    });

    it('does not display the edit button to the owner if the date of the mob is in the past', () => {
        const oneDayAfterTheMob = DateTime.parseByDate(mobData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheMob);
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: ownerOfTheMob}});

        expect(wrapper.find('.update-button').element).not.toBeVisible()
    });

    it('does not display the join button if the date of the mob is in the past', () => {
        const oneDayAfterTheMob = DateTime.parseByDate(mobData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheMob);
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: outsider}});

        expect(wrapper.find('.join-button').element).not.toBeVisible()
    });

    it('does not display the leave button if the date of the mob is in the past', () => {
        const oneDayAfterTheMob = DateTime.parseByDate(mobData.date).addDays(1).toISOString();
        DateTime.setTestNow(oneDayAfterTheMob);
        wrapper = mount(MobCard, {propsData: {socialMob: mobData, user: attendee}});

        expect(wrapper.find('.leave-button').element).not.toBeVisible()
    });
});
