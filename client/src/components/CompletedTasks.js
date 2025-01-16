import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';

import { apiUrl } from '../config';
import { useLanguage } from '../LanguageContext';
import { formattedDate } from '../utils';

import Header from './Header';

const CompletedTasks = () => {
  const { translate } = useLanguage();

  const [tasks, setTasks] = useState({my: [], active:[], completed:[], declined: []});

  const fetchTasks = async () => {
    try {
      const urlHash = window.location.hash;
      if(urlHash) {
        const tab = document.querySelector('[href="' + urlHash + '"]');
        if (tab) tab.click();
      }

      const response = await fetch(apiUrl + '/tasks/getCompleted', {
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
  }, []);

  return (
    <>
      <Header />
      <div className="container mt-4">
        <h2 className="text-center mb-3">{translate.completed_tasks}</h2>

        <ul className="nav nav-pills row mb-3" role="tablist">
          <li key="co1" className="nav-item col-6 text-center" role="presentation">
            <a className="nav-link rounded-pill px-2 active" data-bs-toggle="tab" href="#active" aria-selected="true" role="tab">{translate.moderation} {tasks.active.length > 0 ? '(' + tasks.active.length + ')' : ''}</a>
          </li>
          <li key="co2" className="nav-item col-6 text-center" role="presentation">
            <a className="nav-link rounded-pill px-2" data-bs-toggle="tab" href="#completed" aria-selected="false" tabIndex="-1" role="tab">{translate.approved} {tasks.completed.length > 0 ? '(' + tasks.completed.length + ')' : ''}</a>
          </li>
          <li key="co3" className="nav-item col-6 offset-3 text-center" role="presentation">
            <a className="nav-link rounded-pill px-2" data-bs-toggle="tab" href="#declined" aria-selected="false" tabIndex="-1" role="tab">{translate.declined} {tasks.declined.length > 0 ? '(' + tasks.declined.length + ')' : ''}</a>
          </li>
        </ul>

        <div className="tab-content">
          <div className="tab-pane fade active show" id="active" role="tabpanel">
            {tasks.active.length === 0 && (
              <div className="alert alert-dismissible alert-light">
                {translate.tasks_awaiting_author_check}
              </div>
            )}
            {tasks.active.map(task => (
              <div key={task.id} className="card mb-3">
                <div className="card-body">
                  <h5 className="card-title">#{task.id} <Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                  <p className="card-subtitle mb-1">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  <p className="card-subtitle mb-2">{translate.done}: <small><i>{formattedDate(task.date)}</i></small></p>
                  <div className="alert alert-dismissible alert-light m-0 py-1">
                    {translate.awaiting_author_check}
                  </div>
                </div>
              </div>
            ))}
          </div>
          <div className="tab-pane fade" id="completed" role="tabpanel">
            {tasks.completed.length === 0 && (
              <div className="alert alert-dismissible alert-light">
                {translate.rewarded_tasks}
              </div>
            )}
            {tasks.completed.map(task => (
              <div key={task.id} className="card mb-3">
                <div className="card-body">
                  <h5 className="card-title">#{task.id} <Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                  <p className="card-subtitle mb-1">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  <p className="card-subtitle mb-2">{translate.done}: <small><i>{formattedDate(task.date)}</i></small></p>
                  <div className="alert alert-dismissible alert-success m-0 py-1">
                    &#10003; {translate.task_completed_rewarded}
                  </div>
                </div>
              </div>
            ))}
          </div>
          <div className="tab-pane fade" id="declined" role="tabpanel">
            {tasks.declined.length === 0 && (
              <div className="alert alert-dismissible alert-light">
                {translate.declined_by_author}
              </div>
            )}
            {tasks.declined.map(task => (
              <div key={task.id} className="card mb-3">
                <div className="card-body">
                  <h5 className="card-title">#{task.id} <Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                  <p className="card-subtitle mb-1">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  <p className="card-subtitle mb-2">{translate.done}: <small><i>{formattedDate(task.date)}</i></small></p>
                  <div className="alert alert-dismissible alert-danger m-0 py-1">
                    🗙 {translate.task_declined}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  );
};

export default CompletedTasks;