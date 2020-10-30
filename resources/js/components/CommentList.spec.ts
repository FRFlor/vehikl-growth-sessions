import {mount, Wrapper} from '@vue/test-utils';
import CommentList from './CommentList.vue';
import {SocialMob} from '../classes/SocialMob';
import socialMobWithCommentsJson from '../../../tests/fixtures/SocialMobWithComments.json';
import userJson from '../../../tests/fixtures/User.json';
import {SocialMobApi} from '../services/SocialMobApi';
import {IUser} from '../types';
import {User} from "../classes/User";
import mobJson from "../../../tests/fixtures/SocialMobWithComments.json";

const socialMob: SocialMob = new SocialMob(socialMobWithCommentsJson);
const user: IUser = userJson;

describe('CommentList', () => {
    let wrapper: Wrapper<CommentList>;

    beforeEach(() => {
        wrapper = mount(CommentList, {propsData: {socialMob, user}})
    });

    it('displays all comments of a given mob', () => {
        socialMob.comments.map(comment => comment.content).forEach(comment => {
            expect(wrapper.text()).toContain(comment);
        });
    });

    it('allows a new comment to be created', async () => {
        SocialMobApi.postComment = jest.fn().mockResolvedValue(socialMob);
        const comment = 'My comment';

        wrapper.find('#new-comment').setValue(comment);
        wrapper.find('form').trigger('submit');

        expect(SocialMobApi.postComment).toHaveBeenCalled();
    });

    it('disables the new comment form for guests',() => {
        wrapper = mount(CommentList, {propsData: {socialMob, user: null}})

        expect(wrapper.find('#new-comment').element).toBeDisabled();
        expect(wrapper.find('#submit-new-comment').element).toBeDisabled();
    });

    it('redirects to the commenters GitHub page when clicked on the avatar', () => {
        const commenterComponent = wrapper.findAllComponents({ref: 'commenter-avatar-link'})

        commenterComponent.wrappers.forEach((attendeeComponent, i) => {
            const commenter = new User(socialMob.comments[i].user);
            expect(attendeeComponent.element).toHaveAttribute('href', commenter.githubURL);
        })
    })
});
