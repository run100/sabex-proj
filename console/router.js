import { createRouter, createWebHistory } from 'vue-router';
import AccessLogsPanel from './panels/AccessLogsPanel.vue';
import CodesPanel from './panels/CodesPanel.vue';
import ItemsPanel from './panels/ItemsPanel.vue';
import JobsPanel from './panels/JobsPanel.vue';
import SitesPanel from './panels/SitesPanel.vue';
import NewsPanel from './panels/NewsPanel.vue';
import TradeEmailCodesPanel from './panels/TradeEmailCodesPanel.vue';
import TradeJoinsPanel from './panels/TradeJoinsPanel.vue';
import TradeReportsPanel from './panels/TradeReportsPanel.vue';
import TradeUsersPanel from './panels/TradeUsersPanel.vue';
import TradesPanel from './panels/TradesPanel.vue';

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/items' },
        { path: '/items', component: ItemsPanel },
        { path: '/sites', component: SitesPanel },
        { path: '/news', component: NewsPanel },
        { path: '/codes', component: CodesPanel },
        { path: '/users', component: TradeUsersPanel },
        { path: '/listings', component: TradesPanel },
        { path: '/joins', component: TradeJoinsPanel },
        { path: '/reports', component: TradeReportsPanel },
        { path: '/email-codes', component: TradeEmailCodesPanel },
        { path: '/jobs', component: JobsPanel },
        { path: '/logs', component: AccessLogsPanel },
        { path: '/:pathMatch(.*)*', redirect: '/items' },
    ],
});
