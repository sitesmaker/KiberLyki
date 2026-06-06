import type { StrapiApp } from '@strapi/strapi/admin';

export default {
  config: {
    locales: ["ru"],
    defaultLocale: "ru", 
  },
  bootstrap(app: StrapiApp) {
    console.log(app);
  },
};
