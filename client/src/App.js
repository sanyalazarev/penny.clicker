import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';

import { LanguageProvider } from './LanguageContext';
import { ModalProvider } from './ModalContext';

import Home from './components/Home';
import TaskDetail from './components/TaskDetail';
import AddTask from './components/AddTask';
import EditTask from './components/EditTask';
import CheckTask from './components/CheckTask';
import Profile from './components/Profile';
import EditProfile from './components/EditProfile';
import MyTasks from './components/MyTasks';
import CompletedTasks from './components/CompletedTasks';
import Balance from './components/Balance';
import ModalInfo from './components/ModalInfo';
import ModalError from './components/ModalError';

import { apiUrl, telegram } from './config';

const availableLanguages = ['en', 'uk', 'es', 'ru'];

const App = () => {
  const [isUserValid, setIsUserValid] = useState(null);
  const [language, setLanguage] = useState('en');

  const checkUser = async () => {
    try {
      const response = await fetch(apiUrl + '/user/checkAuth', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ initData: telegram.initData })
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          window.token = data.token;
          window.user = data.user;
          setIsUserValid(true);

          const lang = availableLanguages.indexOf(data.user.language_code) !== -1 ? data.user.language_code : 'en';
          setLanguage(lang);
        }
        else {
          alert(data.error);
          setIsUserValid(false);
        }
      } else {
        setIsUserValid(false);
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  useEffect(() => {
      checkUser();
  }, []);

  if (isUserValid === null) {
      return <div>Verifying user...</div>;
  }

  if (!isUserValid) {
      return <div>Access denied</div>;
  }

  return (
    <LanguageProvider language={language}>
      <ModalProvider>
        <Router>
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/task/:id" element={<TaskDetail />} />
            <Route path="/add-task" element={<AddTask />} />
            <Route path="/task-edit/:id" element={<EditTask />} />
            <Route path="/task-check/:id" element={<CheckTask />} />
            <Route path="/profile" element={<Profile />} />
            <Route path="/edit-profile" element={<EditProfile />} />
            <Route path="/my-tasks" element={<MyTasks />} />
            <Route path="/completed-tasks" element={<CompletedTasks />} />
            <Route path="/balance" element={<Balance />} />
          </Routes>
        </Router>
        <ModalInfo />
        <ModalError />
      </ModalProvider>
    </LanguageProvider>
  );
};

export default App;