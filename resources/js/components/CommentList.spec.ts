import {mount, Wrapper} from '@vue/test-utils';
import CommentList from './CommentList.vue';
import {SocialMob} from '../classes/SocialMob';
import socialMobWithCommentsJson from '../../../tests/fixtures/SocialMobWithComments.json';
import userJson from '../../../tests/fixtures/User.json';
import {SocialMobApi} from '../services/SocialMobApi';
import {IUser} from '../types';

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
});
