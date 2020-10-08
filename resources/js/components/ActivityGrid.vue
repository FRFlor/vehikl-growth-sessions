<template>
    <div class="overflow-hidden rounded shadow-sm hover:shadow-md transition-shadow duration-300 mx-w-sm">
        <div class="px-4 pt-4 pb-2">
            <div class="text-xl font-bold">
                {{title}}
            </div>

            <div class="overflow-hidden" ref="container">
                <div class="grid gap-1" :style="gridStyle">
                    <div class="grid gap-1 grid-rows-5" v-for="(mobs, week) in recentWeeks" :key="`week-${week}`">
                      <div v-for="(dayMobs, day) in mobs" :title="`${day}, ${dayMobs.length} mobs`" :key="`day-${day}`">
                            <div class="w-4 h-4 border border-blue-400 shadow-md" :class="cellColor(dayMobs)"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import moment from 'moment';

    import {ISocialMob} from '../types';
    const firstOfYear = new Date((new Date()).getFullYear(), 0, 1);
    const getWeekNumberFromMob = (mob : ISocialMob) : number => moment(mob.date).isoWeek();

    const weekNumberToDate = (week: number): Date => {
        const year = firstOfYear.getFullYear();
        const basicWeekToDate = new Date(year, 0, 1 + (week - 1) * 7);
        const dayOfWeek = basicWeekToDate.getDay();

        if (dayOfWeek <= 4) {
            basicWeekToDate.setDate(basicWeekToDate.getDate() - basicWeekToDate.getDay() + 1);
            return basicWeekToDate;
        }

        basicWeekToDate.setDate(basicWeekToDate.getDate() + 8 - basicWeekToDate.getDay());
        return basicWeekToDate;
    };

    const weekObjectKey = (week: number): string => {
        const date = weekNumberToDate(week);
        return date.toISOString().split('T')[0];
    };

    const mobsInWeekByDay = (week: string, mobs: ISocialMob[] = []): { [key: string]: ISocialMob[] } => {
        return [0, 1, 2, 3, 4]
            .reduce((data: { [key: string]: ISocialMob[] }, dayOffset: number): { [key: string]: ISocialMob[] } => {
                const date = moment(week).add(dayOffset, 'days');
                const key = date.toISOString().split('T')[0];
                return {
                    ...data,
                    [key]: mobs.filter((mob: ISocialMob) => date.isSame(mob.date, 'day')),
                };
            }, {});
    };

    @Component({components: {}})
    export default class ActivityGrid extends Vue {
        @Prop({default:'Week Grid View'}) title!: string;
        @Prop({required: true}) mobs!: ISocialMob[];
        weekCount: number = 100;

        mounted(): void {
            this.calculateNumberOfWeeks();
            window.addEventListener('resize', this.calculateNumberOfWeeks);
        }

        beforeDestroy(): void {
            window.removeEventListener('resize', this.calculateNumberOfWeeks);
        }

        calculateNumberOfWeeks(): void {
            const { clientWidth } = this.$refs.container;
            this.weekCount = Math.round(clientWidth / (14 + (16 * 0.25)));
        }

        cellColor(mobs: ISocialMob[]): string {
            if (mobs.length === 0) return 'bg-white';
            return `bg-blue-${Math.max(1, Math.min(9, mobs.length * 2))}00`;
        }

        get gridStyle(): string {
          return `grid-template-columns: repeat(${this.weekCount}, 14px)`;
        }

        get allWeeks() : { [key: string]: ISocialMob[] } {
            return this.mobs
                .reduce((weeks: any, mob : ISocialMob) : { [key: string]: ISocialMob[] } => {
                    const week = weekObjectKey(getWeekNumberFromMob(mob));
                    if (!weeks[week]) {
                        weeks[week] = [];
                    }

                    return {
                        ...weeks,
                        [week]: [
                            ...weeks[week],
                            mob,
                        ],
                    };
                }, {});
        }

        get recentWeeks() : { [key: string]: { [key: string]: ISocialMob[] } } {
            const currentWeek = moment().startOf('isoWeek');
            const weeks = Array.from({ length: this.weekCount }, (_, index) => {
                const offset = this.weekCount - index - 1;
                return moment(currentWeek).subtract(offset * 7, 'days').startOf('isoWeek').format('YYYY-MM-DD');
            });
          
            return weeks
                .reduce((lastWeeks: { [key: string]: { [key: string]: ISocialMob[] } }, week : string ) => {
                    return {
                        ...lastWeeks,
                        [week]: mobsInWeekByDay(week, this.allWeeks[week]),
                    };
                }, {});
        }
    }
</script>

