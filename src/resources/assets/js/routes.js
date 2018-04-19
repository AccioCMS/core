import Dashboard from '../../views/components/general/Dashboard.vue';

import Users from '../../views/components/users/Base.vue';
import UsersAll from '../../views/components/users/All.vue';
import UsersCreate from '../../views/components/users/Create.vue';
import UsersUpdate from '../../views/components/users/Update.vue';
import UsersDetails from '../../views/components/users/Details.vue';
import UsersResetPassword from '../../views/components/users/ResetPassword.vue';

import Language from '../../views/components/language/Base.vue';
import LanguageAll from '../../views/components/language/All.vue';
import LanguageCreate from '../../views/components/language/Create.vue';
import LanguageUpdate from '../../views/components/language/Update.vue';

import PostType from '../../views/components/post_type/Base.vue';
import PostTypeAll from '../../views/components/post_type/All.vue';
import PostTypeCreate from '../../views/components/post_type/Create.vue';
import PostTypeUpdate from '../../views/components/post_type/Update.vue';

import CategoryList from '../../views/components/category/List.vue';
import CategoryCreate from '../../views/components/category/Create.vue';
import CategoryUpdate from '../../views/components/category/Update.vue';

import TagList from '../../views/components/tags/List.vue';
import TagCreate from '../../views/components/tags/Create.vue';
import TagUpdate from '../../views/components/tags/Update.vue';

import Media from '../../views/components/media/Base.vue';

import Permissions from '../../views/components/permissions/Base.vue';
import PermissionsAll from '../../views/components/permissions/All.vue';
import PermissionsEdit from '../../views/components/permissions/Edit.vue';

import Menu from '../../views/components/menu/Base.vue';
import MenuAll from '../../views/components/menu/All.vue';
import MenuRelated from '../../views/components/menu/Related.vue';

import Post from '../../views/components/posts/Base.vue';
import PostAll from '../../views/components/posts/All.vue';
import PostCreate from '../../views/components/posts/Create.vue';
import PostUpdate from '../../views/components/posts/Update.vue';

import CustomFields from '../../views/components/custom_fields/Base.vue';
import CustomFieldsAll from '../../views/components/custom_fields/All.vue';
import CustomFieldsEdit from '../../views/components/custom_fields/Edit.vue';

import ProjectSettings from '../../views/components/project_settings/Base.vue';
import ProjectSettingsGeneral from '../../views/components/project_settings/General.vue';
import ProjectSettingsNotifications from '../../views/components/project_settings/Notifications.vue';
import ProjectSettingsPermalinks from '../../views/components/project_settings/Permalinks.vue';
import ProjectSettingsAnalytics from '../../views/components/project_settings/Analytics.vue';

export var routes = [
    { path: globalProjectDirectory+'/:adminPrefix/', component: Dashboard },
    { path: globalProjectDirectory+'/:adminPrefix/:lang', component: Dashboard },

    { path: globalProjectDirectory+'/:adminPrefix/:lang/user', component: Users, children: [
        { path: 'list', component: UsersAll, name: 'user-list', meta: { module: 'user' } },
        { path: 'create', component: UsersCreate, name: 'user-create', meta: { module: 'user' } },
        { path: 'update/:id', component: UsersUpdate, name: 'user-update', meta: { module: 'user' } },
        { path: 'details/:id', component: UsersDetails, name: 'user-details', meta: { module: 'user' } },
        { path: 'reset-password/:id', component: UsersResetPassword, name: 'user-reset', meta: { module: 'user' } },
        { path: 'search/:term', component: UsersAll, name: 'user-search', meta: { module: 'user' } },
    ]},

    { path: globalProjectDirectory+'/:adminPrefix/:lang/language', component: Language, children: [
        { path: 'list', component: LanguageAll, name: 'language-list', meta: { module: 'language' } },
        { path: 'create', component: LanguageCreate, name: 'language-create', meta: { module: 'language' } },
        { path: 'update/:id', component: LanguageUpdate, name: 'language-update', meta: { module: 'language' } },
    ]},

    { path: globalProjectDirectory+'/:adminPrefix/:lang/post-type', component: PostType, children: [
        { path: 'list', component: PostTypeAll, name: 'post-type-list', meta: { module: 'post-type' } },
        { path: 'create', component: PostTypeCreate, name: 'post-type-create', meta: { module: 'post-type' } },
        { path: 'update/:id', component: PostTypeUpdate, name: 'post-type-update', meta: { module: 'post-type' } },
        { path: 'categorylist/:id', component: CategoryList, name: 'category-list', meta: { module: 'posts' } },
        { path: 'category/:id/search/:term', component: CategoryList, name: 'category-search', meta: { module: 'posts' } },
        { path: 'categorycreate/:id', component: CategoryCreate, name: 'category-create', meta: { module: 'posts' } },
        { path: 'categoryupdate/:id', component: CategoryUpdate, name: 'category-update', meta: { module: 'posts' } },
        { path: 'taglist/:id', component: TagList, name: 'tag-list', meta: { module: 'posts' } },
        { path: 'tags/:id/search/:term', component: TagList, name: 'tag-search', meta: { module: 'posts' } },
        { path: 'tagcreate/:id', component: TagCreate, name: 'tag-create', meta: { module: 'posts' } },
        { path: 'tagupdate/:id', component: TagUpdate, name: 'tag-update', meta: { module: 'posts' } },
    ]},

    { path: globalProjectDirectory+'/:adminPrefix/:lang/media/:view', component: Media, meta: { module: 'media' } },

    { path: globalProjectDirectory+'/:adminPrefix/:lang/permissions', component: Permissions, children: [
        { path: 'list', component: PermissionsAll, name: 'permission-list', meta: { module: 'settings' } },
        { path: 'edit/:id', component: PermissionsEdit, name: 'permission-edit', meta: { module: 'settings' } },
    ]},

    { path: globalProjectDirectory+'/:adminPrefix/:lang/menu', component: Menu, children: [
        { path: 'list/:id', component: MenuAll, name: 'menu-list', meta: { module: 'menu' } } ,
        { path: 'related', component: MenuRelated, name: 'menu-related', meta: { module: 'menu' } },
    ]},

    { path: globalProjectDirectory+'/:adminPrefix/:lang/posts', component: Post, children: [
        { path: ':post_type/list', component: PostAll, name: 'post-list', meta: { module: 'posts' } },
        { path: 'search/:post_type/:term', component: PostAll, name: 'post-search', meta: { module: 'posts' } },
        { path: ':post_type/create', component: PostCreate, name: 'post-create', meta: { module: 'posts' } },
        { path: ':post_type/update/:id', component: PostUpdate, name: 'post-update', meta: { module: 'posts' } },
    ]},

    { path: globalProjectDirectory+'/:adminPrefix/:lang/custom-fields', component: CustomFields, children: [
        { path: 'list', component: CustomFieldsAll, name: 'custom-fields-list', meta: { module: 'custom-fields' } },
        { path: 'create', component: CustomFieldsEdit, name: 'custom-fields-create', meta: { module: 'custom-fields' } },
        { path: 'update/:id', component: CustomFieldsEdit, name: 'custom-fields-update', meta: { module: 'custom-fields' } },
    ]},

    { path: globalProjectDirectory+'/:adminPrefix/:lang/settings', component: ProjectSettings, children: [
        { path: 'general', component: ProjectSettingsGeneral, name: 'project-settings-general', meta: { module: 'project-settings' } },
        { path: 'permalinks', component: ProjectSettingsPermalinks, name: 'project-settings-permalinks', meta: { module: 'project-settings' } },
        { path: 'notifications', component: ProjectSettingsNotifications, name: 'project-settings-notifications', meta: { module: 'project-settings' } },
        { path: 'analytics', component: ProjectSettingsAnalytics, name: 'project-settings-analytics', meta: { module: 'project-settings' } },
    ]},
];
