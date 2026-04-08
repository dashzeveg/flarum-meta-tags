import app from 'flarum/forum/app';

export { default as extend } from './extend';

app.initializers.add('dashzeveg-meta-tags', () => {
  console.log('[dashzeveg/flarum-meta-tags] Hello, forum!');
});
