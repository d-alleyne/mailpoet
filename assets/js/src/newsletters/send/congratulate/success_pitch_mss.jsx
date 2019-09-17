import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import WelcomeWizardStepLayoutBody from '../../../wizard/layout/step_layout_body.jsx';

function PitchMss(props) {
  return (
    <div className="mailpoet_congratulate_success">
      <h1>{MailPoet.I18n.t('congratulationsMSSPitchHeader')}</h1>
      <WelcomeWizardStepLayoutBody
        illustrationUrl={props.MSS_pitch_illustration_url}
        displayProgressBar={false}
      >
        HERE COMES THE BODY
      </WelcomeWizardStepLayoutBody>
    </div>
  );
}

PitchMss.propTypes = {
  MSS_pitch_illustration_url: PropTypes.string.isRequired,
};


export default PitchMss;
