Welcome to the bugger tool developed in cooperation with Box Inc.

This is a tool for defect prediction. With it, one can define and measure metrics on a commit repository. The tool then associates commits with bugs from a ticket-tracking system and bugginess score for each commit. With this information in hand, the tool can then calculate correlations between metrics and bugginess, and finally, can learn a decision tree which can be used for defect prediction of new commits.

Setup:

To mine data from code repositories (in our case Git), bug tracking systems (in our case Jira), and code review databases (in our case Gerrit), Box proprietary APIs were used. These have not been included in this public repository of the bugger tool. Instead, interfaces of the required proprietary classes have been provided in the External/ folder. Therefore, the tool cannot work out-of-the-box until those classes are implemented. However, this does allow for relatively straightforward adaptation to tooling other than Git, Jira, and Gerrit.

Running:

The tool is run through three commands, each located in the bin/ directory: risk_mine, risk_metrics, and risk_predict. Typically they are run in that order. Please see each command's help text for more information, which can be viewed by running the command with the help argument (e.g. ./bin/risk_mine.php --help).

Feedback:

If you encounter any problems or have any questions, we are happy to hear your feedback! You can contact us at christine dot ger at phei dot de!