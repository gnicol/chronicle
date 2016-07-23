<table style="width:100%;">
  <tbody>
    <tr>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td id="dashboard">
        <? foreach ($instances as $id => $instance): ?>
          <table style="width:100%;margin: 10px;">
            <tbody>
              <tr>
                <td class="status-dull">
                  <div class="good p4">
                    <div>
                      <table>
                        <tbody>
                          <tr>
                            <td class="play" rowspan="2">
                              <a href="<? echo $instance['url']; ?>">
                              </a>
                            </td>
                            <td class="left">
                              <a href="<? echo $instance['url']; ?>">
                                Chronicle - <? echo $instance['title']; ?>
                              </a>
                              (user: p4cms / blank password / p4port: <? echo $instance['p4port']; ?>)
                            </td>
                            <td class="right"><? echo $instance['changeNo']; ?></td>
                          </tr>
                          <tr>
                            <td class="left status-dull">
                              <em>last sync'ed (<? echo $instance['changeDate']; ?>) <a href="/syncInstance.php?instance=<? echo $id; ?>">sync now</a></em>
                            </td>
                            <td class="right">
                              <? if ($instance['allowReset']) { ?>
                              <em><a href="/resetDepot.php?instance=<? echo $id; ?>" onclick="javascript:return confirm('Are you sure you want to re-run setup for this site?\nExisting data will be lost.');">Re-run setup</a></em>
                              <? } ?>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        <? endforeach; ?>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
  </tbody>
</table>
