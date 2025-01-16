import React, { useState, useEffect, useContext } from 'react';
import { Link } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';
import { ModalContext } from '../ModalContext';
import { formattedDate } from '../utils';

import Header from './Header';

const MyTasks = () => {
  const { translate } = useLanguage();

  const { showError, showInfo } = useContext(ModalContext);

  const [tasks, setTasks] = useState({moderation:[], active:[], declined: [], archive: []});

  const fetchTasks = async () => {
    try {
      const response = await fetch(apiUrl + '/tasks/getMy', {
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
          showError(data.error);
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

  const handleClickStop = async (task_id) => {
    // eslint-disable-next-line no-restricted-globals
    if(confirm(translate.stop_task_confirmation)) {
      try {
        const response = await fetch(apiUrl + '/tasks/stop', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ token: window.token, task_id })
        });

        if (response.ok) {
          const data = await response.json();

          if(data.success)
            fetchTasks();
          else
            showError(data.error);
        } else {
          console.error(response.statusText);
        }
      } catch (error) {
        console.error('Error loading data:', error);
      }
    }
  };

  const handleClickEdit = (e, showMsg) => {
    if(showMsg) {
      e.preventDefault();

      showInfo(translate.unverified_requests);
    }
  }

  return (
    <>
      <Header />

      <div className="container mt-4 mb-3">
        <h2 className="text-center mb-3">{translate.my_tasks}</h2>

        <Link to="/add-task" className="btn btn-warning w-100 rounded-pill mb-3">&#10010; {translate.add_new_task}</Link>

        <ul className="nav nav-pills row" role="tablist">
          <li key="co2" className="nav-item col-6 text-center" role="presentation">
            <a className="nav-link rounded-pill px-2 active" data-bs-toggle="tab" href="#active" aria-selected="false" tabIndex="-1" role="tab">{translate.active} {tasks.active.length > 0 ? '(' + tasks.active.length + ')' : ''}</a>
          </li>
          <li key="co1" className="nav-item col-6 text-center" role="presentation">
            <a className="nav-link rounded-pill px-2" data-bs-toggle="tab" href="#moderation" aria-selected="true" role="tab">{translate.moderation} {tasks.moderation.length > 0 ? '(' + tasks.moderation.length + ')' : ''}</a>
          </li>
          <li key="co3" className="nav-item col-6 text-center" role="presentation">
            <a className="nav-link rounded-pill px-2" data-bs-toggle="tab" href="#declined" aria-selected="false" tabIndex="-1" role="tab">{translate.declined} {tasks.declined.length > 0 ? '(' + tasks.declined.length + ')' : ''}</a>
          </li>
          <li key="co4" className="nav-item col-6 text-center" role="presentation">
            <a className="nav-link rounded-pill px-2" data-bs-toggle="tab" href="#archive" aria-selected="false" tabIndex="-1" role="tab">{translate.archive} {tasks.archive.length > 0 ? '(' + tasks.archive.length + ')' : ''}</a>
          </li>
        </ul>
      </div>

      <div className="container mt-4">
        <div className="tab-content">
          <div className="tab-pane fade active show" id="active" role="tabpanel">
            {tasks.active.length === 0 && (
              <div className="alert alert-dismissible alert-light">
                {translate.in_progress_tasks}
              </div>
            )}
            {tasks.active.map(task => (
              <div key={task.id} className="card mb-3">
                <div className="card-body">
                  <h5 className="card-title">#{task.id} <Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                  <p className="card-subtitle mb-1">{translate.progress}: {task.numberCompleted}/{task.numberExecutions}</p>
                  <p className="card-subtitle mb-1">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  <p className="card-subtitle mb-1">{translate.balance}: <span className="badge rounded-pill bg-success">{task.balance} {task.currency}</span></p>
                  <p className="card-subtitle mb-2">{translate.deadline}: <small><i>{formattedDate(task.deadline, false)}</i></small></p>
                  <div className="btn-group w-100" role="group">
                    <Link to={`/task-edit/${task.id}`} className="btn btn-outline-success btn-sm" onClick={(e) => handleClickEdit(e, task.numberModeration > 0)}>{translate.edit}</Link>
                    <button className="btn btn-outline-danger btn-sm" onClick={() => handleClickStop(task.id)}>{translate.stop}</button>
                    <Link to={`/task-check/${task.id}`} className={task.numberModeration ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary'}>{translate.awaiting_check}({task.numberModeration})</Link>
                  </div>
                </div>
              </div>
            ))}
          </div>

          <div className="tab-pane fade" id="moderation" role="tabpanel">
            {tasks.moderation.length === 0 && (
              <div className="alert alert-dismissible alert-light">
                {translate.under_moderation_tasks}
              </div>
            )}
            {tasks.moderation.map(task => (
              <div key={task.id} className="card mb-3">
                <div className="card-body">
                  <h5 className="card-title">#{task.id} <Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                  <p className="card-subtitle mb-1">{translate.progress}: {task.numberCompleted}/{task.numberExecutions}</p>
                  <p className="card-subtitle mb-1">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  <p className="card-subtitle mb-1">{translate.balance}: <span className="badge rounded-pill bg-success">{task.balance} {task.currency}</span></p>
                  <p className="card-subtitle mb-2">{translate.deadline}: <small><i>{formattedDate(task.deadline, false)}</i></small></p>
                  <div className="btn-group w-100" role="group">
                    <Link to={`/task-edit/${task.id}`} className="btn btn-outline-success btn-sm" onClick={(e) => handleClickEdit(e, task.numberModeration > 0)}>{translate.edit}</Link>
                    <button className="btn btn-outline-danger btn-sm" onClick={() => handleClickStop(task.id)}>{translate.stop}</button>
                  </div>
                </div>
              </div>
            ))}
          </div>

          <div className="tab-pane fade" id="declined" role="tabpanel">
            {tasks.declined.length === 0 && (
              <div className="alert alert-dismissible alert-light">
              {translate.moderation_failed_tasks}
              </div>
            )}
            {tasks.declined.map(task => (
              <div key={task.id} className="card mb-3">
                <div className="card-body">
                  <h5 className="card-title">#{task.id} <Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                  <p className="card-subtitle mb-1">{translate.progress}: {task.numberCompleted}/{task.numberExecutions}</p>
                  <p className="card-subtitle mb-1">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  <p className="card-subtitle mb-1">{translate.balance}: <span className="badge rounded-pill bg-success">{task.balance} {task.currency}</span></p>
                  <p className="card-subtitle mb-2">{translate.deadline}: <small><i>{formattedDate(task.deadline, false)}</i></small></p>
                  <div className="btn-group w-100" role="group">
                    <Link to={`/task-edit/${task.id}`} className="btn btn-outline-success btn-sm" onClick={(e) => handleClickEdit(e, task.numberModeration > 0)}>{translate.edit}</Link>
                    <Link to={`/task-check/${task.id}`} className={task.numberModeration ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary'}>{translate.awaiting_check}({task.numberModeration})</Link>
                  </div>
                </div>
              </div>
            ))}
          </div>

          <div className="tab-pane fade" id="archive" role="tabpanel">
            {tasks.archive.length === 0 && (
              <div className="alert alert-dismissible alert-light">
              {translate.completed_or_stopped_tasks}
              </div>
            )}
            {tasks.archive.map(task => (
              <div key={task.id} className="card mb-3">
                <div className="card-body">
                  <h5 className="card-title">#{task.id} <Link to={`/task/${task.id}`}>{task.title}</Link></h5>
                  <p className="card-subtitle mb-1">{translate.progress}: {task.numberCompleted}/{task.numberExecutions}</p>
                  <p className="card-subtitle mb-1">{translate.price}: <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
                  <p className="card-subtitle mb-1">{translate.balance}: <span className="badge rounded-pill bg-success">{task.balance} {task.currency}</span></p>
                  <p className="card-subtitle">{translate.deadline}: <small><i>{formattedDate(task.deadline, false)}</i></small></p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  );
};

export default MyTasks;