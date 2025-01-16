import { siteUrl } from './config';

export const formattedDate = (date, showHours=true) => {
  const dateObj = new Date(date);
  const currentYear = new Date().getFullYear();
  const dateYear = dateObj.getFullYear();

  const options = {
    day: 'numeric',
    month: 'short'
  };

  if(showHours) {
    options.hour = '2-digit';
    options.minute = '2-digit';
    options.hour12 = false;
  }

  // Add the year only if it is different from the current one
  if (dateYear !== currentYear) {
    options.year = 'numeric';
  }

  return new Intl.DateTimeFormat('en-US', options).format(dateObj);
};

export const getPhoto = (photo) => {
  return photo ? siteUrl + '/' + photo : 'logo.png';
};