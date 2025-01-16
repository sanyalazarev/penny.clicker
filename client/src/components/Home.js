import React, { useState, useEffect, useContext } from 'react';
import { Link } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';
import { ModalContext } from '../ModalContext';

import Header from './Header';

const Home = () => {
  const { translate } = useLanguage();

  const { showInfo } = useContext(ModalContext);

  const [tasks, setTasks] = useState([]);

  const fetchTasks = async () => {
    try {
      const response = await fetch(apiUrl + '/tasks/getAvailable ', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token })
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success)
          setTasks(data.tasks);
        else
          alert(data.error);
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  useEffect(() => {
    fetchTasks();
    if(window.user && window.user.new)  {
      showInfo(translate.welcom_title, translate.welcom_message);
      window.user.new = false; 
    }
  }, []);

  return (
    <>
      <Header />
      <div className="container mt-4">
        <h2 className="text-center mb-3">{translate.available_tasks}</h2>

        <div className="card-columns">
          {tasks.length === 0 && (
            <div className="alert alert-dismissible alert-light">
              {translate.no_available_tasks}<br></br> <Link to="/edit-profile">{translate.connect_notification}.</Link>
            </div>
          )}
          {tasks.map(task => (
            <div key={task.id} className="card mb-2">
              <div className="card-body">
                <div className="row">
                  <div className="col-9">
                    <h5 className="card-title"><Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                    <p className="card-subtitle mb-0">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  </div>
                  <div className="col-3 d-flex align-items-center justify-content-end">
                    <Link to={`/task/${task.id}`} className="btn btn-sm btn-primary rounded-pill">{translate.show}</Link>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </>
  );
};

export default Home;