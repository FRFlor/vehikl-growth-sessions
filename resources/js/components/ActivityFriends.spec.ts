import {mount, Wrapper} from "@vue/test-utils";
import ActivityFriends from './ActivityFriends.vue';

describe('Activity/Friends', () => {
    let wrapper: Wrapper<ActivityFriends>;

    it('renders an empty table', () => {
        wrapper = mount(ActivityFriends, {propsData: {
            peers: [],
        }});

        expect(wrapper.find('table').exists()).toBe(true);
        expect(wrapper.find('thead').exists()).toBe(true);
        expect(wrapper.find('td').exists()).toBe(false);
    });

    it('renders a single peer', () => {
        wrapper = mount(ActivityFriends, {propsData: {
            peers: [
                {
                    count: 1,
                    user: {
                        id: 1,
                        name: 'Your Name Here',
                        email: '',
                        avatar: '',
                    }
                }
            ],
        }});

        const cells = wrapper.findAll('td');
        expect(cells.at(0).text()).toBe('Your Name Here');
        expect(cells.at(1).text()).toBe('100%');
    });

    it('orders peers by most growth to least', () => {
        wrapper = mount(ActivityFriends, {propsData: {
            peers: [
                {
                    count: 1,
                    user: {
                        id: 1,
                        name: 'Least',
                        email: '',
                        avatar: '',
                    }
                },
                {
                    count: 2,
                    user: {
                        id: 2,
                        name: 'Most',
                        email: '',
                        avatar: '',
                    }
                }
            ],
        }});

        const rows = wrapper.findAll('tbody tr');
        expect(rows.at(0).findAll('td').at(0).text()).toBe('Most');
        expect(rows.at(0).findAll('td').at(1).text()).toBe('67%');
        expect(rows.at(1).findAll('td').at(0).text()).toBe('Least');
        expect(rows.at(1).findAll('td').at(1).text()).toBe('33%');
    });

    it('shows the total number of mobs you have joined', () => {
        wrapper = mount(ActivityFriends, {propsData: {
            peers: [
                {
                    count: 1,
                    user: {
                        id: 1,
                        name: 'Least',
                        email: '',
                        avatar: '',
                    }
                },
                {
                    count: 2,
                    user: {
                        id: 2,
                        name: 'Most',
                        email: '',
                        avatar: '',
                    }
                }
            ],
        }});

        expect(wrapper.find('footer').text()).toBe(
            'Based on the 3 growth sessions you\'ve participated in.',
        );
    });
})
