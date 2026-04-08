import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('dashzeveg-meta-tags', () => {
  console.log('[dashzeveg/flarum-meta-tags] Hello, admin!');
});
