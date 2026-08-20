// resources/js/utils/date.js
const APP_TZ = 'Asia/Manila';

export const todayPH = () => {
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: APP_TZ,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(new Date()); // en-CA locale formats as YYYY-MM-DD
};

export const nowPH = () => {
  return new Date(
    new Date().toLocaleString('en-US', { timeZone: APP_TZ })
  );
};

export const formatPH = (date, options = {}) => {
  return new Intl.DateTimeFormat('en-PH', {
    timeZone: APP_TZ,
    ...options,
  }).format(new Date(date));
};