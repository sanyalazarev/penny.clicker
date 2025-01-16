import React, { useState, useContext } from 'react';
import { Link, useNavigate } from 'react-router-dom';

import { apiUrl } from '../config';

import { useLanguage } from '../LanguageContext';
import { ModalContext } from '../ModalContext';

import Header from './Header';

const AddTask = () => {
  const { translate } = useLanguage();

  const { showError, showInfo } = useContext(ModalContext);

  const navigate = useNavigate();

  const [task, setTask] = useState({
    url: '',
    title: '',
    description: '',
    numberExecutions: '',
    price: '',
    currency: 'TON',
    mode: 0, 
    keyword: '', 
    deadline: ''
  });

  const [showKeyIntut, setShowKeyIntut] = useState(false);

  const [totalPrice, setTotalPrice] = useState(0);

  const handleChange = (e) => {
    setTask({ ...task, [e.target.name]: e.target.value });

    if(e.target.name === "mode") {
      if(e.target.value == 0)
        setShowKeyIntut(false);
      else
        setShowKeyIntut(true);
    }
    else if(e.target.name === 'numberExecutions') {
      const numberExecutions = parseInt(e.target.value) || 0;
      setTotalPrice(parseFloat((numberExecutions * task.price).toFixed(8)));
    }
    else if(e.target.name === 'price') {
      const price = parseFloat(e.target.value) || 0;
      setTotalPrice(parseFloat((price * task.numberExecutions).toFixed(8)));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    try {
      const response = await fetch(apiUrl + '/tasks/add', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: window.token, ...task }),
      });

      if (response.ok) {
        const data = await response.json();

        if(data.success) {
          showInfo(translate.task_added_review, 
            translate.task_added_successfully, 
            () => { navigate('/my-tasks') }
          );
        }
        else {
          showError(data.error);
        }
      } else {
        console.error(response.statusText);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    }
  };

  return (
    <>
      <Header />
      <div className="container mt-3 px-4">
        <form onSubmit={handleSubmit}>
          <h3 className="text-center">{translate.add_new_task}</h3>
          <div className="row">
            <label htmlFor="titleInput" className="form-label">{translate.task_title}</label>
            <input
              type="text" 
              name="title" 
              className="form-control rounded-pill" 
              id="titleInput" 
              placeholder={translate.task_title_placeholder} 
              onChange={handleChange} 
              required 
            />
          </div>
          <div className="row">
            <label htmlFor="urlInput" className="form-label mt-3">URL</label>
            <input 
              type="text" 
              name="url" 
              className="form-control rounded-pill" 
              id="urlInput" 
              placeholder="https://" 
              onChange={handleChange} 
              required 
            />
          </div>
          <div className="row">
            <label htmlFor="descriptionInput" className="form-label mt-3">{translate.task_description}</label>
            <textarea
              name="description" 
              className="form-control" 
              id="descriptionInput" 
              rows="3" 
              placeholder={translate.task_description_placeholder} 
              onChange={handleChange} 
              required>
            </textarea>
          </div>
          <div className="row">
            <label htmlFor="numberExecutionsInput" className="form-label mt-3">{translate.number_of_executions}</label>
            <input
              type="text"
              name="numberExecutions"
              className="form-control rounded-pill"
              id="numberExecutionsInput"
              placeholder="1000"
              onChange={handleChange}
              required
            />
          </div>
          <div className="row">
            <div className="col">
              <label htmlFor="priceInput" className="form-label mt-3">{translate.price}</label>
              <input 
                type="text" 
                name="price"
                className="form-control rounded-pill" 
                id="priceInput" 
                placeholder="0.01" 
                onChange={handleChange} 
                required 
              />
            </div>
            <div className="col">
              <label htmlFor="currencySelect" className="form-label mt-3">{translate.cryptocurrency}</label>
              <select 
                name="currency" 
                className="form-select rounded-pill" 
                id="currencySelect"
                onChange={handleChange}
              >
                <option value="TON">TON</option>
                <option value="NOT">NOT</option>
                <option value="DOGS">DOGS</option>
                <option value="HMSTR">HMSTR</option>
                <option value="X">X</option>
              </select>
            </div>
          </div>
          {totalPrice > 0 && (
            <div className="row">
              <label htmlFor="optionsMode" className="form-label mt-3"><b>{translate.total_price}:</b> {totalPrice} {task.currency.toUpperCase()}</label>
            </div>
          )}
          <div className="row">
            <label htmlFor="optionsMode" className="form-label mt-3">{translate.check_mode}</label>
          </div>
          <div className="form-check">
            <input
              type="radio"
              name="mode"
              value="0"
              className="form-check-input"
              id="optionsMode1"
              onClick={handleChange}
              defaultChecked
            />
            <label htmlFor="optionsMode1" className="form-check-label">
              {translate.manual}
            </label>
          </div>
          <div className="form-check">
            <input
              type="radio"
              name="mode"
              value="1"
              className="form-check-input"
              id="optionsMode2"
              onClick={handleChange}
            />
            <label htmlFor="optionsMode2" className="form-check-label">
              {translate.auto}
            </label>
          </div>
          {showKeyIntut && (
            <div className="row">
              <label htmlFor="urlInput" className="form-label mt-3">{translate.keyword}</label>
              <input
                type="text"
                name="keyword"
                className="form-control rounded-pill"
                id="keyInput"
                placeholder=""
                onChange={handleChange}
              />
            </div>
          )}
          <div className="row">
            <label htmlFor="deadlineInput" className="form-label mt-3">{translate.deadline}</label>
            <input
              type="date"
              name="deadline"
              className="form-control rounded-pill"
              id="deadlineInput"
              placeholder=""
              onChange={handleChange}
              required
            />
          </div>
          <div className="row">
            <button type="submit" className="btn btn-primary rounded-pill mt-3">&#10003; {translate.save}</button>
            <Link to="/my-tasks" className="btn btn-light w-100 rounded-pill mt-2 mb-3">
              &larr; {translate.back_to_tasks}
            </Link>
          </div>
        </form>
      </div>
    </>
  );
};

export default AddTask;