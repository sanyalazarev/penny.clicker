import React, { createContext, useState } from 'react';

export const ModalContext = createContext();

export const ModalProvider = ({ children }) => {
  const [error, setError] = useState('');
  const [info, setInfo] = useState({ title: '', text: '', callback: '' });

  const showError = (text) => setError(text);
  const showInfo = (title, text, callback) => {
    setInfo({ title, text, callback: (typeof callback === 'function' ? callback : '') });
  }

  const clearError = () => setError('');
  const clearInfo = () => {
    if(typeof info.callback === 'function') info.callback();
    setInfo({ title: '', text: '', callback: '' });
  }

  return (
    <ModalContext.Provider value={{ error, info, showError, showInfo, clearError, clearInfo }}>
      {children}
    </ModalContext.Provider>
  );
};