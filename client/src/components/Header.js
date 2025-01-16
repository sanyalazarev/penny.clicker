import React from 'react';
import { Link } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';

import {IcoLogo} from './Icons';

const Header = () => {
  const { language, translate, setLanguage } = useLanguage();

  const handleChangeLanguage = async (e) => {
    setLanguage(e.target.value);

    try {
      const response = await fetch(apiUrl + '/user/setLanguage', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token: window.token, 
          language: e.target.value
        })
      });

      if (response.ok) {
        const data = await response.json();

        if(!data.success) {
          alert(data.error);
        }
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  }

  return (
    <nav className="navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
      <div className="container-fluid">
        <IcoLogo />

        <Link to="/" className="navbar-brand">Penny Clicker</Link>

        <button className="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
          <span className="navbar-toggler-icon"></span>
        </button>

        <div className="navbar-collapse collapse" id="navbarColor01">
          <ul className="navbar-nav me-auto">
            <li key="h2" className="nav-item">
              <Link to="/" className="nav-link">{translate.available_tasks}</Link>
            </li>
            <li key="h1" className="nav-item">
              <Link to="/profile" className="nav-link">{translate.profile}</Link>
            </li>
            <li key="h3" className="nav-item">
              <Link to="/my-tasks" className="nav-link">{translate.my_tasks}</Link>
            </li>
            <li key="h4" className="nav-item">
              <Link to="/completed-tasks" className="nav-link">{translate.completed_tasks}</Link>
            </li>
            <li key="h5" className="nav-item">
              <Link to="/balance" className="nav-link">{translate.balance}</Link>
            </li>
            <li key="h6" className="nav-item d-flex justify-content-between" style={{ width: '100%'}}>
              <span className="nav-link">{translate.language}</span>
              <select className="form-select rounded-pill me-3" style={{ width: 'auto' }} value={language} onChange={handleChangeLanguage}>
                <option value="en">English</option>
                <option value="uk">Українська</option>
                <option value="es">Español</option>
                <option value="ru">Русский</option>
              </select>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  );
};

export default Header;