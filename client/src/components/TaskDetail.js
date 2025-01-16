import React, { useState, useEffect, useContext } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';
import { ModalContext } from '../ModalContext';

import Header from './Header';

const TaskDetail = () => {
  const { translate } = useLanguage();

  const { showError, showInfo } = useContext(ModalContext);

  const navigate = useNavigate();

  const onClickBack = () => { navigate(-1) };

  const { id: task_id } = useParams();

  const [task, setTask] = useState({title: '', description: '', url: '', price: 0, currency: '', mode: 1, completed: 1});
  const [showCompleteButton, setShowCompleteButton] = useState(true);
  const [showCheckButton, setShowCheckButton] = useState(false);
  const [showhowKeyInput, setShowKeyInput] = useState(false);

  // Function to get task from server
  const fetchTask = async () => {
    try {
      const response = await fetch(apiUrl + '/tasks/getById', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token, task_id }),
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          setTask(data.task);
        }
        else
          showError(data.error);
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  const handleCompleteClick = async () => {
    try {
      const response = await fetch(apiUrl + '/tasks/startDoing', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token: window.token, 
          task_id, 
          keyword: (task.mode == 1 ? document.getElementById("keyInput").value : '')
        })
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          if(task.mode == 0)
            showInfo(translate.task_complete, translate.task_complete_message_author);
          else if(task.mode == 1)
            showInfo(translate.task_complete, translate.task_complete_message_user);

          setShowKeyInput(false);
          setShowCheckButton(false);
        } else
          showError(data.error);
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  const handleGoClick = async () => {
    setShowCompleteButton(false);

    if(task.mode == 1)
      setShowKeyInput(true);

    setShowCheckButton(true);
  }

  useEffect(() => {
    fetchTask();
  }, []);

  return (
    <>
      <Header />

      <div className="container mt-4">
        <h2 className="text-primary mb-3"><u>{task.title}</u></h2>
        <p className="mt-0 mb-1"><b>{translate.price}:</b> <span className="badge rounded-pill bg-success">{task.price} {task.currency}</span></p>
        {task.description && (
          <p className="mt-0 mb-3">
            {task.description.split('\n').map((line, index) => (
              <React.Fragment key={index}>
                {line}
                <br />
              </React.Fragment>
            ))}
          </p>
        )}

        {!task.completed && showCompleteButton && (
          <Link to={task.url} target="_blank" className="btn btn-primary w-100 rounded-pill" onClick={handleGoClick}>
            {translate.execute} &rarr;
          </Link>
        )}

        {!task.completed && showhowKeyInput && (
          <input 
            type="text" 
            name="key" 
            className="form-control mb-2" 
            id="keyInput" 
            placeholder={translate.verification_code}
          />
        )}

        {!task.completed && showCheckButton && (
          <button className="btn btn-success w-100 rounded-pill" onClick={handleCompleteClick}>
            &#10003; {translate.check_execution}
          </button>
        )}

        <button className="btn btn-light mt-2 w-100 rounded-pill" onClick={onClickBack}>&larr; {translate.back_to_tasks}</button>
      </div>
    </>
  );
};

export default TaskDetail;