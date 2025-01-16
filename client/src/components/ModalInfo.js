import React, { useContext, useEffect } from 'react';
import { ModalContext } from '../ModalContext';

const ModalInfo = () => {
  const { info, clearInfo } = useContext(ModalContext);

  useEffect(() => {
    if (info.title) {
      document.querySelectorAll('.modal.show .btn-close').forEach(button => {
        button.click();
      });

      const modal = new window.bootstrap.Modal(document.getElementById('modalInfo'));
      modal.show();
    }

    document.getElementById('modalInfo').addEventListener('hidden.bs.modal', function () {
      clearInfo()
    });
  }, [info]); // If the error changes, the window will be opened

  return (
    <div className="modal fade" id="modalInfo" tabIndex="-1" aria-labelledby="modalInfoLabel" aria-hidden="true">
      <div className="modal-dialog">
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title" id="modalInfoLabel">{info.title}</h5>
            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div className="modal-body" dangerouslySetInnerHTML={{ __html: info.text.replace('\n', '<br />') }} />
          <div className="modal-footer">
            <button type="button" className="btn btn-primary" data-bs-dismiss="modal" onClick={clearInfo}>Ok</button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ModalInfo;